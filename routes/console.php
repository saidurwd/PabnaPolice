<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly backup of the database and uploads (storage/app/private/<app name>), then prune old ones.
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');

// Expired password-reset tokens, and a regular self-check whose warnings/failures land in the log.
Schedule::command('auth:clear-resets')->daily();
Schedule::command('app:health --log')->hourly();

// Keep the visit/audit logs from growing without bound.
Schedule::command('logs:prune --days=180')->weeklyOn(0, '02:30');
