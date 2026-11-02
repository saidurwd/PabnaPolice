<?php

namespace Tests\Feature;

use App\Support\HealthCheck;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_sent_on_public_and_admin_pages(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');

        $response = $this->get('/admin/login');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_health_endpoint_reports_ok_without_leaking_details(): void
    {
        $this->get('/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_health_details_need_the_token(): void
    {
        config(['app.health_token' => 'secret-token']);

        $this->get('/health?token=wrong')->assertOk()->assertJsonMissingPath('checks');
        $this->get('/health?token=secret-token')->assertOk()->assertJsonPath('checks.database.status', 'ok')->assertJsonStructure(['checks' => ['migrations', 'storage']]);
    }

    public function test_health_check_fails_with_503_when_a_migration_is_pending(): void
    {
        DB::table('migrations')->orderByDesc('id')->limit(1)->delete();

        $this->get('/health')->assertStatus(503)->assertExactJson(['status' => 'fail']);
        $this->assertSame('fail', HealthCheck::overall((new HealthCheck)->run()));
    }

    public function test_health_warns_about_debug_mode_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true]);

        $this->assertSame('fail', (new HealthCheck)->run()['debug_mode']['status']);
    }

    public function test_app_health_command_runs(): void
    {
        $this->artisan('app:health')->assertSuccessful();
    }

    /** `config:cache` stops loading .env, so env() outside config/ silently returns null in production. */
    public function test_env_is_only_used_inside_config_files(): void
    {
        $offenders = collect(['app', 'bootstrap', 'routes', 'resources', 'database'])
            ->flatMap(fn ($dir) => File::allFiles(base_path($dir)))
            ->filter(fn ($f) => $f->getExtension() === 'php' && ! str_contains($f->getPathname(), '/database/migrations/'))
            ->filter(fn ($f) => preg_match('/\benv\(/', File::get($f->getPathname())))
            ->map(fn ($f) => str_replace(base_path().'/', '', $f->getPathname()))
            ->values()->all();

        $this->assertSame([], $offenders, 'Move these env() calls into a config/ file.');
    }

    public function test_every_scheduled_command_exists(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($e) => $e->command)->all();

        $this->assertNotEmpty($commands);
        foreach (['backup:run', 'backup:clean', 'logs:prune', 'auth:clear-resets', 'app:health'] as $name) {
            $this->assertTrue(collect($commands)->contains(fn ($c) => str_contains((string) $c, $name)), "{$name} is not scheduled");
            $this->assertArrayHasKey($name, Artisan::all(), "{$name} is not a registered command");
        }
    }

    public function test_csp_is_report_only_by_default_and_can_be_enforced_or_disabled(): void
    {
        $header = fn () => $this->get('/')->headers;

        $h = $header();
        $this->assertStringContainsString("default-src 'self'", (string) $h->get('Content-Security-Policy-Report-Only'));
        $this->assertStringContainsString('report-uri', (string) $h->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($h->get('Content-Security-Policy'));

        config(['security.csp_mode' => 'enforce']);
        $h = $header();
        $this->assertNotNull($h->get('Content-Security-Policy'));
        $this->assertNull($h->get('Content-Security-Policy-Report-Only'));

        config(['security.csp_mode' => 'off']);
        $h = $header();
        $this->assertNull($h->get('Content-Security-Policy'));
        $this->assertNull($h->get('Content-Security-Policy-Report-Only'));
    }

    public function test_csp_extra_sources_are_added(): void
    {
        config(['security.csp_extra_sources' => 'https://cdn.example.org']);

        $this->assertStringContainsString('script-src \'self\' \'unsafe-inline\' https://cdn.example.org', (string) $this->get('/')->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_csp_violation_reports_are_accepted_without_a_csrf_token_and_logged(): void
    {
        $log = storage_path('logs/csp-'.now()->format('Y-m-d').'.log');
        @unlink($log);

        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], json_encode([
            'csp-report' => ['document-uri' => 'http://x.test/', 'effective-directive' => 'img-src', 'blocked-uri' => 'http://evil.test/a.png'],
        ]))->assertNoContent();

        $this->assertFileExists($log);
        $this->assertStringContainsString('img-src', file_get_contents($log));
        $this->assertStringContainsString('http://evil.test/a.png', file_get_contents($log));
        @unlink($log);

        // Garbage must not break it either.
        $this->call('POST', '/csp-report', [], [], [], [], 'not json')->assertNoContent();
        @unlink($log);
    }

    public function test_health_warns_when_backups_are_only_on_the_server_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['backup.backup.destination.disks' => ['local']]);
        $this->assertSame('warn', (new HealthCheck)->run()['offsite_backup']['status']);

        config(['backup.backup.destination.disks' => ['local', 's3']]);
        $this->assertSame('ok', (new HealthCheck)->run()['offsite_backup']['status']);
    }

    /** Regression: a copied .env.example has BACKUP_NOTIFY_EMAIL= (empty), which used to crash every artisan command. */
    public function test_backup_config_loads_with_an_empty_notification_address(): void
    {
        $previous = $_ENV['BACKUP_NOTIFY_EMAIL'] ?? null;
        $_ENV['BACKUP_NOTIFY_EMAIL'] = $_SERVER['BACKUP_NOTIFY_EMAIL'] = '';
        putenv('BACKUP_NOTIFY_EMAIL=');

        try {
            $config = require config_path('backup.php');
            $this->assertSame([], $config['notifications']['notifications'][BackupHasFailedNotification::class]);
            $this->assertTrue((bool) filter_var($config['notifications']['mail']['to'], FILTER_VALIDATE_EMAIL));
        } finally {
            $previous === null ? putenv('BACKUP_NOTIFY_EMAIL') : putenv("BACKUP_NOTIFY_EMAIL={$previous}");
            unset($_ENV['BACKUP_NOTIFY_EMAIL'], $_SERVER['BACKUP_NOTIFY_EMAIL']);
        }
    }
}
