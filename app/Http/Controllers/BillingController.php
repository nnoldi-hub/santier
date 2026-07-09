<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
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
        $tenant = $user?->currentTenant ?: $user?->tenant;
        $previousPlan = $tenant?->billing_plan ?: $user->billing_plan;

        if ($tenant instanceof Tenant) {
            $tenant->update([
                'billing_plan' => $validated['plan'],
            ]);
        }

        // Compatibility sync while remaining billing reads are migrated to tenant-level data.
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
