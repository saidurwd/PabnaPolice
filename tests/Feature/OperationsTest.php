<?php

namespace Tests\Feature;

use App\Support\HealthCheck;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
}
