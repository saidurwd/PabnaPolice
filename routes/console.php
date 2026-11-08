<?php

use App\Support\HealthCheck;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// DISABLED: production PHP has no ext-zip. Re-enable all three once zip is available.
// // Nightly backup of the database and uploads (storage/app/private/<app name>), then prune old ones.
// Schedule::command('backup:clean')->dailyAt('01:00');
// Schedule::command('backup:run')->dailyAt('01:30');
// Schedule::command('backup:monitor')->dailyAt('03:00');   // warns (by e-mail) when the newest backup is too old

// Lets System health tell whether cron is really running the scheduler.
Schedule::call(fn () => Cache::put(HealthCheck::HEARTBEAT_KEY, time(), 3600))->name('scheduler-heartbeat')->everyMinute();

// Expired password-reset tokens, and a regular self-check whose warnings/failures land in the log.
Schedule::command('auth:clear-resets')->daily();
Schedule::command('app:health --log')->hourly();

// Keep the visit/audit logs from growing without bound.
Schedule::command('logs:prune --days=180')->weeklyOn(0, '02:30');
