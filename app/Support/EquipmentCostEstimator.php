<?php

namespace App\Support;

use App\Models\SiteEquipmentPlan;
use App\Models\StageEquipment;
use Carbon\Carbon;

class EquipmentCostEstimator
{
    private const DAILY_HOURS = 8;

    /**
     * Estimates the cost of an equipment reservation as cost_per_hour * quantity *
     * days * daily hours (8h/day). This is the same formula already used by
     * ExportDatasetBuilder::equipment() - kept here as a single reusable source so
     * new pages don't duplicate it (the codebase previously had 2 other, inconsistent,
     * variants of this formula in EquipmentCalendarController and routes/web.php).
     */
    public static function estimate(StageEquipment|SiteEquipmentPlan $reservation): float
    {
        $days = self::reservedDays($reservation);
        $hourlyCost = self::hourlyCost($reservation);
        $quantity = max(1, (int) $reservation->quantity);

        return round($hourlyCost * $quantity * $days * self::DAILY_HOURS, 2);
    }

    /**
     * SiteEquipmentPlan snapshots its own hourly rate at creation time so a later
     * change to Equipment.cost_per_hour doesn't retroactively alter an already
     * committed project plan - StageEquipment has no such snapshot and keeps
     * reading the live catalog rate.
     */
    private static function hourlyCost(StageEquipment|SiteEquipmentPlan $reservation): float
    {
        if ($reservation instanceof SiteEquipmentPlan && $reservation->hourly_rate !== null) {
            return (float) $reservation->hourly_rate;
        }

        return (float) ($reservation->equipment?->cost_per_hour ?? 0);
    }

    public static function reservedDays(StageEquipment|SiteEquipmentPlan $reservation): int
    {
        if (! $reservation->usage_start || ! $reservation->usage_end) {
            return 1;
        }

        $days = Carbon::parse($reservation->usage_start)->diffInDays(Carbon::parse($reservation->usage_end)) + 1;

        return max(1, (int) $days);
    }
}
