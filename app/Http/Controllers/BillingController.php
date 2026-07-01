<?php

namespace App\Http\Controllers;

use App\Support\PricingPlan;
use App\Support\AnalyticsTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $plans = config('pricing.plans', []);

        return Inertia::render('Billing/Index', [
            'currentPlan' => PricingPlan::current($user),
            'plans' => $plans,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'in:free,starter,pro,enterprise'],
        ]);

        $user = $request->user();
        $previousPlan = $user->billing_plan;

        $user->update([
            'billing_plan' => $validated['plan'],
        ]);

        if ($previousPlan !== $validated['plan'] && in_array($validated['plan'], ['starter', 'pro', 'enterprise'], true)) {
            AnalyticsTracker::track($request, 'trial_upgraded', [
                'from' => $previousPlan,
                'to' => $validated['plan'],
            ], oncePerUser: true);
        }

        return back()->with('success', 'Planul a fost actualizat.');
    }
}
