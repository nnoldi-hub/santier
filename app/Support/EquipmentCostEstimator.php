<?php

namespace App\Support;

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
    public static function estimate(StageEquipment $reservation): float
    {
        $days = self::reservedDays($reservation);
        $hourlyCost = (float) ($reservation->equipment?->cost_per_hour ?? 0);
        $quantity = max(1, (int) $reservation->quantity);

        return round($hourlyCost * $quantity * $days * self::DAILY_HOURS, 2);
    }

    public static function reservedDays(StageEquipment $reservation): int
    {
        if (! $reservation->usage_start || ! $reservation->usage_end) {
            return 1;
        }

        $days = Carbon::parse($reservation->usage_start)->diffInDays(Carbon::parse($reservation->usage_end)) + 1;

        return max(1, (int) $days);
    }
}
