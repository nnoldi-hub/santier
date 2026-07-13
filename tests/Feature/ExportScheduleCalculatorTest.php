<?php

namespace Tests\Feature;

use App\Support\ExportScheduleCalculator;
use Tests\TestCase;

class ExportScheduleCalculatorTest extends TestCase
{
    public function test_daily_lands_roughly_one_day_out(): void
    {
        $next = ExportScheduleCalculator::nextRunAt('daily', '08:00', null);

        $this->assertTrue($next->greaterThan(now()));
        $this->assertTrue($next->lessThanOrEqualTo(now()->addDays(2)));
    }

    public function test_weekly_lands_on_the_requested_weekday(): void
    {
        $next = ExportScheduleCalculator::nextRunAt('weekly', '08:00', 3);

        $this->assertSame(3, $next->dayOfWeek);
        $this->assertTrue($next->greaterThan(now()));
    }

    public function test_monthly_lands_roughly_one_month_out(): void
    {
        $next = ExportScheduleCalculator::nextRunAt('monthly', '08:00', null);

        $this->assertTrue($next->greaterThan(now()->addDays(25)));
        $this->assertTrue($next->lessThanOrEqualTo(now()->addDays(35)));
    }

    public function test_quarterly_lands_roughly_three_months_out(): void
    {
        $next = ExportScheduleCalculator::nextRunAt('quarterly', '08:00', null);

        $this->assertTrue($next->greaterThan(now()->addDays(80)));
        $this->assertTrue($next->lessThanOrEqualTo(now()->addDays(100)));
    }

    public function test_yearly_lands_roughly_one_year_out(): void
    {
        $next = ExportScheduleCalculator::nextRunAt('yearly', '08:00', null);

        $this->assertTrue($next->greaterThan(now()->addDays(350)));
        $this->assertTrue($next->lessThanOrEqualTo(now()->addDays(380)));
    }
}
