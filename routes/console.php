<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command() spawns each due job as a separate OS process (needs
// proc_open), which is disabled on this shared host - every entry below runs
// via Schedule::call()+Artisan::call() instead, in-process, no proc_open.
Schedule::call(fn () => Artisan::call('exports:run-scheduled'))->everyMinute();
Schedule::call(fn () => Artisan::call('emails:send-trial-lifecycle'))->dailyAt('09:00');
Schedule::call(fn () => Artisan::call('demo:refresh'))->dailyAt('03:00');
Schedule::call(fn () => Artisan::call('notifications:send-operational-reminders'))->dailyAt('08:00');
Schedule::call(fn () => Artisan::call('briefing:send-daily'))->everyFiveMinutes();
Schedule::call(fn () => Artisan::call('emails:poll-prospect-inbox'))->everyFiveMinutes();
