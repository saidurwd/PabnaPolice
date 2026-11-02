<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Self-checks used by `php artisan app:health` and GET /health.
 * Each check returns [status, message] where status is "ok", "warn" (look at it) or "fail" (site is broken).
 */
class HealthCheck
{
    /** @return array<string, array{status: string, message: string}> */
    public function run(): array
    {
        $results = [];

        foreach ([
            'database' => $this->database(...),
            'migrations' => $this->migrations(...),
            'storage' => $this->storage(...),
            'uploads' => $this->uploads(...),
            'admin_assets' => $this->adminAssets(...),
            'app_key' => $this->appKey(...),
            'debug_mode' => $this->debugMode(...),
            'mail' => $this->mail(...),
            'backup' => $this->backup(...),
            'disk_space' => $this->diskSpace(...),
        ] as $name => $check) {
            try {
                [$status, $message] = $check();
            } catch (Throwable $e) {
                [$status, $message] = ['fail', $e->getMessage()];
            }
            $results[$name] = ['status' => $status, 'message' => $message];
        }

        return $results;
    }

    /** @param  array<string, array{status: string, message: string}>  $results */
    public static function overall(array $results): string
    {
        $statuses = array_column($results, 'status');

        return in_array('fail', $statuses, true) ? 'fail' : (in_array('warn', $statuses, true) ? 'warn' : 'ok');
    }

    /** @return array{0: string, 1: string} */
    private function database(): array
    {
        DB::select('select 1');

        return ['ok', 'Connected to '.DB::getDatabaseName()];
    }

    /** @return array{0: string, 1: string} */
    private function migrations(): array
    {
        $migrator = app('migrator');
        $files = array_keys($migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')])));
        $pending = array_diff($files, $migrator->getRepository()->getRan());

        return $pending
            ? ['fail', count($pending).' pending migration(s): run php artisan migrate --force']
            : ['ok', 'All '.count($files).' migrations have run'];
    }

    /** @return array{0: string, 1: string} */
    private function storage(): array
    {
        $bad = array_filter(['logs', 'framework/cache', 'framework/sessions', 'framework/views'],
            fn ($dir) => ! is_dir(storage_path($dir)) || ! is_writable(storage_path($dir)));

        return $bad ? ['fail', 'Not writable: storage/'.implode(', storage/', $bad)] : ['ok', 'storage/ is writable'];
    }

    /** @return array{0: string, 1: string} */
    private function uploads(): array
    {
        $dir = public_path('uploads');

        return is_dir($dir) && is_writable($dir)
            ? ['ok', 'public/uploads is writable']
            : ['fail', 'public/uploads is missing or not writable (admin uploads and the website images need it)'];
    }

    /** @return array{0: string, 1: string} */
    private function adminAssets(): array
    {
        return is_file(public_path('build/manifest.json'))
            ? ['ok', 'Admin assets are built']
            : ['fail', 'public/build/manifest.json is missing: run npm ci && npm run build and upload public/build'];
    }

    /** @return array{0: string, 1: string} */
    private function appKey(): array
    {
        return config('app.key') ? ['ok', 'APP_KEY is set'] : ['fail', 'APP_KEY is empty: run php artisan key:generate'];
    }

    /** @return array{0: string, 1: string} */
    private function debugMode(): array
    {
        return app()->isProduction() && config('app.debug')
            ? ['fail', 'APP_DEBUG is true in production: it exposes passwords and settings in error pages']
            : ['ok', 'APP_DEBUG is '.(config('app.debug') ? 'on (not production)' : 'off')];
    }

    /** @return array{0: string, 1: string} */
    private function mail(): array
    {
        return app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)
            ? ['warn', 'MAIL_MAILER is "'.config('mail.default').'": password-reset e-mails will not be delivered']
            : ['ok', 'Mailer: '.config('mail.default')];
    }

    /** @return array{0: string, 1: string} */
    private function backup(): array
    {
        $files = glob(storage_path('app/private/*/*.zip')) ?: [];
        if (! $files) {
            return [app()->isProduction() ? 'warn' : 'ok', 'No backups found (nightly backup:run needs the scheduler in cron)'];
        }

        $age = (time() - max(array_map('filemtime', $files))) / 3600;

        return $age > 36
            ? ['warn', 'Newest backup is '.round($age).' hours old']
            : ['ok', 'Newest backup is '.round($age).' hours old'];
    }

    /** @return array{0: string, 1: string} */
    private function diskSpace(): array
    {
        $free = disk_free_space(base_path());
        if ($free === false) {
            return ['ok', 'Free space unknown'];
        }

        $gb = round($free / 1073741824, 1);

        return $free < 200 * 1048576 ? ['fail', "Only {$gb} GB free"] : ($free < 1073741824 ? ['warn', "Only {$gb} GB free"] : ['ok', "{$gb} GB free"]);
    }
}
