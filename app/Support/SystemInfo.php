<?php

namespace App\Support;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/** Facts about the server and the application, for the System health and About pages. */
class SystemInfo
{
    /** @return array<string, string> */
    public static function server(): array
    {
        $dbVersion = 'unknown';
        try {
            $dbVersion = (string) DB::selectOne('select version() v')->v;
        } catch (Throwable) {
        }

        return [
            'PHP' => PHP_VERSION.' ('.PHP_SAPI.')',
            'Laravel' => app()->version(),
            'Database server' => $dbVersion,
            'Web server' => (string) ($_SERVER['SERVER_SOFTWARE'] ?? PHP_SAPI),
            'Operating system' => php_uname('s').' '.php_uname('r'),
            'Server time' => now()->format('d M Y H:i:s').' ('.config('app.timezone').')',
            'Environment' => app()->environment().(config('app.debug') ? ' · debug ON' : ''),
            'Cache / session / queue' => config('cache.default').' / '.config('session.driver').' / '.config('queue.default'),
            'PHP memory limit' => (string) ini_get('memory_limit'),
            'Upload limit' => ini_get('upload_max_filesize').' (post '.ini_get('post_max_size').')',
            'Max execution time' => ini_get('max_execution_time').' s',
        ];
    }

    /** @return array{free: float, total: float, used_percent: int}|null in bytes */
    public static function disk(): ?array
    {
        $free = @disk_free_space(base_path());
        $total = @disk_total_space(base_path());

        return $free === false || ! $total ? null : ['free' => $free, 'total' => $total, 'used_percent' => (int) round(100 - $free / $total * 100)];
    }

    /** @return array{size_mb: float, tables: int, latency_ms: float} */
    public static function database(): array
    {
        $start = microtime(true);
        $row = DB::selectOne(
            'select count(*) t, coalesce(sum(data_length + index_length), 0) s from information_schema.tables where table_schema = ?',
            [DB::getDatabaseName()]
        );

        return ['size_mb' => round($row->s / 1048576, 1), 'tables' => (int) $row->t, 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
    }

    /** Size of the upload and storage folders, cached for ten minutes (walking the folders is not free). */
    public static function folders(): array
    {
        return Cache::remember('system.folder-sizes', 600, fn () => [
            'Uploaded files' => self::dirSize(public_path('uploads')),
            'Logs' => self::dirSize(storage_path('logs')),
            'Backups' => self::dirSize(storage_path('app/private')),
        ]);
    }

    /** How many ERROR / CRITICAL lines the application log gained in the last 24 hours (reads the newest 2 MB). */
    public static function recentLogErrors(): ?int
    {
        $file = storage_path('logs/laravel.log');
        if (! is_file($file)) {
            return 0;
        }

        $size = filesize($file);
        $handle = @fopen($file, 'r');
        if (! $handle) {
            return null;
        }
        fseek($handle, max(0, $size - 2 * 1048576));
        $text = (string) stream_get_contents($handle);
        fclose($handle);

        $since = now()->subDay();
        $count = 0;
        if (preg_match_all('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\][^\n]*\.(?:ERROR|CRITICAL|ALERT|EMERGENCY):/m', $text, $m)) {
            foreach ($m[1] as $when) {
                $count += $when >= $since->format('Y-m-d H:i:s') ? 1 : 0;
            }
        }

        return $count;
    }

    /** @return array<string, string> the main packages and their versions */
    public static function packages(): array
    {
        $out = [];
        foreach (['laravel/framework' => 'Laravel framework', 'spatie/laravel-permission' => 'Roles & permissions', 'spatie/laravel-backup' => 'Backups', 'pragmarx/google2fa' => 'Two-factor authentication'] as $name => $label) {
            $out[$label] = InstalledVersions::isInstalled($name) ? ltrim((string) InstalledVersions::getPrettyVersion($name), 'v') : 'not installed';
        }

        $bootstrap = @json_decode((string) @file_get_contents(base_path('node_modules/bootstrap/package.json')), true)['version'] ?? null;
        $out['Admin interface'] = 'Bootstrap '.($bootstrap ?? '5.3');
        $out['Public theme'] = 'Bootstrap 5.3.8 (with a Bootstrap 3 compatibility layer)';

        return $out;
    }

    /** The deployed code version: APP_VERSION, plus the short git commit when the folder is a git checkout. */
    public static function version(): array
    {
        $commit = null;
        $head = base_path('.git/HEAD');
        if (is_file($head)) {
            $ref = trim((string) file_get_contents($head));
            if (str_starts_with($ref, 'ref: ')) {
                $refFile = base_path('.git/'.substr($ref, 5));
                $commit = is_file($refFile) ? substr(trim((string) file_get_contents($refFile)), 0, 7) : null;
            } else {
                $commit = substr($ref, 0, 7);
            }
        }

        return ['version' => (string) config('app.version'), 'commit' => $commit];
    }

    private static function dirSize(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $size = 0;
        try {
            foreach (File::allFiles($dir) as $f) {
                $size += $f->getSize();
            }
        } catch (Throwable) {
        }

        return $size;
    }

    public static function bytes(int|float $b): string
    {
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $i => $unit) {
            if ($b < 1024 || $unit === 'TB') {
                return round($b, $i ? 1 : 0).' '.$unit;
            }
            $b /= 1024;
        }

        return '';
    }
}
