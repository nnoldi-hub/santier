<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\PricingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    private const PAID_PLANS = ['start', 'pro'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenant = PricingPlan::tenant($user);
        $subscription = $tenant?->subscription('default');

        return Inertia::render('Billing/Index', [
            'currentPlan' => PricingPlan::current($user),
            'plans' => collect(config('pricing.plans', []))->only(['free', 'start', 'pro'])->all(),
            'subscription' => $subscription ? [
                'onGracePeriod' => $subscription->onGracePeriod(),
                'active' => $subscription->active(),
                'endsAt' => optional($subscription->ends_at)->toDateString(),
                'interval' => PricingPlan::intervalForStripePrice($subscription->stripe_price),
            ] : null,
        ]);
    }

    public function checkout(Request $request, string $plan)
    {
        abort_unless(in_array($plan, self::PAID_PLANS, true), 404);

        $interval = $request->query('interval', 'monthly');
        abort_unless(in_array($interval, ['monthly', 'yearly'], true), 422);

        $tenant = $this->resolveTenant($request);
        abort_if($tenant->subscribed('default'), 422, 'Tenantul are deja un abonament activ - foloseste schimbarea de plan.');

        $priceId = PricingPlan::priceIdForPlan($plan, $interval);
        abort_if(!$priceId, 500, 'Planul nu are un Price Stripe configurat.');

        // Stripe rejects a checkout session that specifies both `customer` and
        // `customer_email` - Cashier always sends `customer` (it resolves/creates
        // the Stripe customer for the tenant), so the email must be set on the
        // customer itself beforehand instead of passed to checkout().
        if (!$tenant->hasStripeId()) {
            $tenant->createOrGetStripeCustomer([
                'email' => $request->user()->email,
                'name' => $tenant->name,
            ]);
        }

        return $tenant->newSubscription('default', $priceId)->checkout([
            'success_url' => route('billing.index') . '?checkout=success',
            'cancel_url' => route('billing.index') . '?checkout=cancelled',
        ]);
    }

    public function swap(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'plan' => ['required', 'in:' . implode(',', self::PAID_PLANS)],
            'interval' => ['nullable', 'in:monthly,yearly'],
        ]);
        abort_if($validator->fails(), 422, $validator->errors()->first());
        $validated = $validator->validated();

        $tenant = $this->resolveTenant($request);
        abort_unless($tenant->subscribed('default'), 422, 'Nu exista niciun abonament activ de schimbat.');

        $priceId = PricingPlan::priceIdForPlan($validated['plan'], $validated['interval'] ?? 'monthly');
        abort_if(!$priceId, 500, 'Planul nu are un Price Stripe configurat.');

        $tenant->subscription('default')->swap($priceId);
        $tenant->update(['billing_plan' => $validated['plan']]);

        return back()->with('success', 'Planul a fost schimbat.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $tenant = $this->resolveTenant($request);
        abort_unless($tenant->subscribed('default'), 422, 'Nu exista niciun abonament activ de anulat.');

        $tenant->subscription('default')->cancel();

        return back()->with('success', 'Abonamentul a fost anulat - accesul ramane activ pana la finalul perioadei deja platite.');
    }

    public function resume(Request $request): RedirectResponse
    {
        $tenant = $this->resolveTenant($request);
        $subscription = $tenant->subscription('default');
        abort_unless($subscription && $subscription->onGracePeriod(), 422, 'Nu exista o anulare programata de revocat.');

        $subscription->resume();

        return back()->with('success', 'Anularea a fost revocata - abonamentul continua normal.');
    }

    public function portal(Request $request)
    {
        $tenant = $this->resolveTenant($request);
        abort_unless($tenant->hasStripeId(), 422, 'Tenantul nu are inca un cont Stripe.');

        return $tenant->redirectToBillingPortal(route('billing.index'));
    }

    private function resolveTenant(Request $request): Tenant
    {
        $tenant = PricingPlan::tenant($request->user());
        abort_unless($tenant instanceof Tenant, 422, 'Contul nu este asociat unui tenant.');

        return $tenant;
    }
}
