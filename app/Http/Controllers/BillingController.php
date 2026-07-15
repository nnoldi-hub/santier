<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\AnalyticsTracker;
use App\Support\PricingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    private const PAID_PLANS = ['starter', 'pro', 'enterprise'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenant = PricingPlan::tenant($user);
        $subscription = $tenant?->subscription('default');

        return Inertia::render('Billing/Index', [
            'currentPlan' => PricingPlan::current($user),
            'plans' => config('pricing.plans', []),
            'subscription' => $subscription ? [
                'onGracePeriod' => $subscription->onGracePeriod(),
                'active' => $subscription->active(),
                'endsAt' => optional($subscription->ends_at)->toDateString(),
            ] : null,
        ]);
    }

    public function checkout(Request $request, string $plan)
    {
        abort_unless(in_array($plan, self::PAID_PLANS, true), 404);

        $tenant = $this->resolveTenant($request);
        abort_if($tenant->subscribed('default'), 422, 'Tenantul are deja un abonament activ - foloseste schimbarea de plan.');

        $priceId = PricingPlan::priceIdForPlan($plan);
        abort_if(!$priceId, 500, 'Planul nu are un Price Stripe configurat.');

        return $tenant->newSubscription('default', $priceId)->checkout([
            'customer_email' => $request->user()->email,
            'success_url' => route('billing.index') . '?checkout=success',
            'cancel_url' => route('billing.index') . '?checkout=cancelled',
        ]);
    }

    public function swap(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'in:' . implode(',', self::PAID_PLANS)],
        ]);

        $tenant = $this->resolveTenant($request);
        abort_unless($tenant->subscribed('default'), 422, 'Nu exista niciun abonament activ de schimbat.');

        $priceId = PricingPlan::priceIdForPlan($validated['plan']);
        abort_if(!$priceId, 500, 'Planul nu are un Price Stripe configurat.');

        $previousPlan = PricingPlan::current($request->user());

        $tenant->subscription('default')->swap($priceId);
        $tenant->update(['billing_plan' => $validated['plan']]);

        if ($previousPlan !== $validated['plan']) {
            AnalyticsTracker::track($request, 'trial_upgraded', [
                'from' => $previousPlan,
                'to' => $validated['plan'],
            ], oncePerUser: true);
        }

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
