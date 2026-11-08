<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\AuditTrail;
use App\Models\SecurityEvent;
use Illuminate\Console\Command;

class PruneLogs extends Command
{
    protected $signature = 'logs:prune
        {--days=180 : Delete login history older than this many days}
        {--activity-days=365 : Delete the activity log older than this many days}
        {--security-days=365 : Delete security events older than this many days}
        {--audit-days=730 : Delete the audit log (data changes) older than this many days}';

    protected $description = 'Delete old login history, activity log, security events and audit log rows';

    public function handle(): int
    {
        $ago = fn (string $option) => now()->subDays((int) $this->option($option));

        $logins = AuditTrail::where('login_time', '<', $ago('days'))->delete();
        $activity = ActivityLog::where('log', 'activity')->where('created_at', '<', $ago('activity-days'))->delete();
        $audit = ActivityLog::where('log', 'audit')->where('created_at', '<', $ago('audit-days'))->delete();
        $security = SecurityEvent::where('created_at', '<', $ago('security-days'))->delete();

        $this->info("Deleted {$logins} login history, {$activity} activity, {$audit} audit and {$security} security rows.");

        return self::SUCCESS;
    }
}
