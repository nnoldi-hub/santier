<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('exports:run-scheduled')->everyMinute();
Schedule::command('emails:send-trial-lifecycle')->dailyAt('09:00');
Schedule::command('demo:refresh')->dailyAt('03:00');
Schedule::command('notifications:send-operational-reminders')->dailyAt('08:00');
Schedule::command('briefing:send-daily')->everyFiveMinutes();
