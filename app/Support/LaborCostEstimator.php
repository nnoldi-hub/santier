<?php

namespace App\Support;

use App\Models\SiteStaffPlan;
use Carbon\Carbon;

class LaborCostEstimator
{
    private const DAILY_HOURS = 8;

    /**
     * Estimates the cost of a staff plan as hourly_rate * headcount * days *
     * daily hours (8h/day) - same formula/shape as EquipmentCostEstimator,
     * kept as a mirror so both domains stay consistent. hourly_rate lives on
     * the plan itself (not derived from Team/TeamMember.hourly_rate, which is
     * per-person, not per-headcount-plan, and often the team isn't assigned
     * yet at planning time).
     */
    public static function estimate(SiteStaffPlan $plan): float
    {
        return round((float) ($plan->hourly_rate ?? 0) * self::estimatedHours($plan), 2);
    }

    public static function estimatedHours(SiteStaffPlan $plan): float
    {
        return round(self::plannedDays($plan) * self::DAILY_HOURS * max(1, (int) $plan->planned_headcount), 2);
    }

    public static function plannedDays(SiteStaffPlan $plan): int
    {
        if (! $plan->planned_start || ! $plan->planned_end) {
            return 1;
        }

        $days = Carbon::parse($plan->planned_start)->diffInDays(Carbon::parse($plan->planned_end)) + 1;

        return max(1, (int) $days);
    }
}
