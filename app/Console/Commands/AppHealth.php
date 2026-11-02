<?php

namespace App\Console\Commands;

use App\Support\HealthCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AppHealth extends Command
{
    protected $signature = 'app:health {--log : Write warnings/failures to the application log (used by the scheduler)}';

    protected $description = 'Check the database, migrations, folders, assets, backups and configuration';

    public function handle(HealthCheck $health): int
    {
        $results = $health->run();
        $icons = ['ok' => '<info>OK  </info>', 'warn' => '<comment>WARN</comment>', 'fail' => '<error>FAIL</error>'];

        foreach ($results as $name => $r) {
            $this->line(sprintf('%s  %-13s %s', $icons[$r['status']], $name, $r['message']));

            if ($this->option('log') && $r['status'] !== 'ok') {
                Log::log($r['status'] === 'fail' ? 'error' : 'warning', "Health check {$name}: {$r['message']}");
            }
        }

        return HealthCheck::overall($results) === 'fail' ? self::FAILURE : self::SUCCESS;
    }
}
