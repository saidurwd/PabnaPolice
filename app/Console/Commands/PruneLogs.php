<?php

namespace App\Console\Commands;

use App\Models\AuditTrail;
use Illuminate\Console\Command;

class PruneLogs extends Command
{
    protected $signature = 'logs:prune {--days=180 : Delete sign-in history older than this many days}';

    protected $description = 'Delete old admin sign-in history rows';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $logins = AuditTrail::where('login_time', '<', $cutoff)->delete();

        $this->info("Deleted {$logins} sign-in history rows older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
