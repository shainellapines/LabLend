<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| The scheduler (FR-N3): Laravel checks this list every minute when `php artisan schedule:work` is running
| (locally) or when a cron job calls `php artisan schedule:run` (on a server), and runs what is due.
*/
Schedule::command('lablend:send-overdue-reminders')
    ->dailyAt(config('lablend.overdue_reminder_time')) // 08:00, from config/lablend.php
    ->timezone(config('app.timezone'));

// FR-N4 (optional): every hour, cancel pending requests whose start time has passed without approval.
Schedule::command('lablend:expire-pending')->hourly();
