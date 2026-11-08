<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Support\HealthCheck;
use App\Support\SystemInfo;

/** System health (live self-checks and server facts) and About (what is installed). */
class SystemController extends Controller
{
    public function health(HealthCheck $health)
    {
        $checks = $health->run();

        return view('admin.system.health', [
            'checks' => $checks,
            'overall' => HealthCheck::overall($checks),
            'server' => SystemInfo::server(),
            'disk' => SystemInfo::disk(),
            'database' => SystemInfo::database(),
            'folders' => SystemInfo::folders(),
            'logErrors' => SystemInfo::recentLogErrors(),
            'security' => [
                'failed' => SecurityEvent::where('type', 'login_failed')->where('created_at', '>=', now()->subDay())->count(),
                'critical' => SecurityEvent::where('severity', 'critical')->where('created_at', '>=', now()->subDay())->count(),
            ],
            'checkedAt' => now(),
        ]);
    }

    public function about()
    {
        return view('admin.system.about', [
            'release' => SystemInfo::version(),
            'server' => SystemInfo::server(),
            'packages' => SystemInfo::packages(),
        ]);
    }
}
