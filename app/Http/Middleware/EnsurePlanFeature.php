<?php

namespace App\Http\Middleware;

use App\Support\PricingPlan;
use Closure;
use Illuminate\Http\Request;

class EnsurePlanFeature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature)
    {
        $user = $request->user();

        if (!$user || PricingPlan::hasFeature($user, $feature)) {
            return $next($request);
        }

        return redirect()->route('dashboard')->with('error', PricingPlan::featureMessage($feature));
    }
}
