<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class ExportScheduleCalculator
{
    public static function nextRunAt(string $frequency, string $time, ?int $weekday): Carbon
    {
        $now = now();

        return match ($frequency) {
            'daily' => $now->copy()->addDay()->setTimeFromTimeString($time),
            'monthly' => $now->copy()->addMonthNoOverflow()->setTimeFromTimeString($time),
            'quarterly' => $now->copy()->addMonthsNoOverflow(3)->setTimeFromTimeString($time),
            'yearly' => $now->copy()->addYearNoOverflow()->setTimeFromTimeString($time),
            default => $now->copy()->next($weekday ?? 1)->setTimeFromTimeString($time),
        };
    }
}
