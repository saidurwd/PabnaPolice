<?php

namespace App\Console\Commands;

use App\Models\AuditTrail;
use App\Models\Visitor;
use Illuminate\Console\Command;

class PruneLogs extends Command
{
    protected $signature = 'logs:prune {--days=180 : Delete visitor and audit rows older than this many days}';

    protected $description = 'Delete old admin visitor-log and login audit rows';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) $this->option('days'));

        $visits = Visitor::where('server_time', '<', $cutoff)->delete();
        $logins = AuditTrail::where('login_time', '<', $cutoff)->delete();

        $this->info("Deleted {$visits} visitor rows and {$logins} audit rows older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
