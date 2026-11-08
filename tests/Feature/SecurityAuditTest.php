<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AuditTrail;
use App\Models\Category;
use App\Models\Content;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private function boss(): User
    {
        return User::factory()->superUser()->create(['username' => 'boss', 'email' => 'boss@example.com', 'full_name' => 'The Boss']);
    }

    public function test_failed_sign_ins_are_recorded_and_the_lockout_is_critical(): void
    {
        $this->boss();
        RateLimiter::clear('admin-login:boss|127.0.0.1');

        $this->post('/admin/login', ['username' => 'nobody', 'password' => 'x']);
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'wrong']);

        $unknown = SecurityEvent::where('type', 'login_failed')->where('identifier', 'nobody')->firstOrFail();
        $this->assertSame('warning', $unknown->severity);
        $this->assertNull($unknown->user_id);
        $this->assertNotNull(SecurityEvent::where('type', 'login_failed')->where('identifier', 'boss')->value('user_id'));

        foreach (range(1, 5) as $i) {
            $this->post('/admin/login', ['username' => 'boss', 'password' => 'wrong']);
        }
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'wrong']);
        $this->assertSame('critical', SecurityEvent::where('type', 'login_locked')->firstOrFail()->severity);
    }

    public function test_a_sign_in_is_recorded_with_ip_and_browser_then_the_sign_out_closes_it(): void
    {
        $this->boss();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/120 Safari/537')
            ->post('/admin/login', ['username' => 'boss', 'password' => 'secret12'])->assertRedirect();

        $row = AuditTrail::firstOrFail();
        $this->assertSame('127.0.0.1', $row->ip_address);
        $this->assertSame('Chrome on Windows (Desktop)', $row->browser);
        $this->assertNull($row->logout_time);
        $this->assertTrue(ActivityLog::where('log', 'activity')->where('event', 'login')->exists());

        $this->post('/admin/logout');
        $this->assertNotNull($row->fresh()->logout_time);
        $this->assertTrue(ActivityLog::where('event', 'logout')->exists());
    }

    public function test_signing_in_from_a_new_address_raises_a_warning_but_the_first_ever_sign_in_does_not(): void
    {
        $user = $this->boss();
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'secret12']);
        $this->assertSame(0, SecurityEvent::where('type', 'login_new_ip')->count());

        $this->post('/admin/logout');
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->post('/admin/login', ['username' => 'boss', 'password' => 'secret12']);

        $event = SecurityEvent::where('type', 'login_new_ip')->firstOrFail();
        $this->assertSame('203.0.113.9', $event->ip_address);
        $this->assertSame($user->id, $event->user_id);
    }

    public function test_data_changes_go_to_the_audit_log_with_old_and_new_values_and_never_passwords(): void
    {
        $admin = $this->boss();
        $cat = Category::factory()->create();

        $this->actingAs($admin)->post('/admin/content', [
            'title' => 'First title', 'category_id' => $cat->id, 'status' => 'Active', 'details' => '<p>x</p>',
        ])->assertRedirect();
        $content = Content::where('title', 'First title')->firstOrFail();
        $this->assertTrue(ActivityLog::where('log', 'audit')->where('event', 'created')->where('subject_type', 'Content')->where('subject_id', $content->id)->exists());

        $this->put("/admin/content/{$content->id}", ['title' => 'Second title', 'category_id' => $cat->id, 'status' => 'Active', 'details' => '<p>x</p>']);
        $log = ActivityLog::where('event', 'updated')->where('subject_id', $content->id)->latest('id')->firstOrFail();
        $this->assertSame('First title', $log->properties['old']['title']);
        $this->assertSame('Second title', $log->properties['new']['title']);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('The Boss', $log->user_name);

        $this->delete("/admin/content/{$content->id}");
        $this->assertTrue(ActivityLog::where('event', 'trashed')->where('subject_id', $content->id)->exists());
        $this->post("/admin/content/{$content->id}/restore");
        $this->assertTrue(ActivityLog::where('event', 'restored')->where('subject_id', $content->id)->exists());

        $admin->update(['password' => 'changed-hash', 'full_name' => 'Renamed Boss']);
        $userLog = ActivityLog::where('subject_type', 'User')->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertArrayNotHasKey('password', $userLog->properties['new']);
        $this->assertSame('Renamed Boss', $userLog->properties['new']['full_name']);
    }

    public function test_changes_made_without_a_signed_in_user_are_not_audited(): void
    {
        Category::factory()->create();

        $this->assertSame(0, ActivityLog::where('log', 'audit')->count());
    }

    public function test_password_and_role_changes_are_security_events(): void
    {
        $admin = $this->boss();
        PermissionCatalog::sync();

        $this->actingAs($admin)->post('/admin/role', ['name' => 'Publisher', 'permissions' => ['content.view']]);
        $this->put('/admin/role/'.Role::findByName('Publisher')->id, ['name' => 'Publisher', 'permissions' => ['content.view', 'content.create']]);
        $changed = SecurityEvent::where('type', 'role_changed')->latest('id')->firstOrFail();
        $this->assertSame(['content.create'], $changed->details['added']);

        $this->put('/admin/account/password', ['current_password' => 'secret12', 'password' => 'Another99', 'password_confirmation' => 'Another99']);
        $this->assertTrue(SecurityEvent::where('type', 'password_changed')->where('user_id', $admin->id)->exists());
    }

    public function test_a_refused_page_is_recorded_and_only_permitted_users_see_the_log_screens(): void
    {
        PermissionCatalog::sync();
        Role::findOrCreate('Editor', 'web')->syncPermissions(['content.view']);
        $editor = User::factory()->role('Editor')->create(['username' => 'ed', 'email' => 'ed@example.com']);

        $this->actingAs($editor)->get('/admin/audit-log')->assertForbidden();
        $this->get('/admin/system-health')->assertForbidden();

        $event = SecurityEvent::where('type', 'permission_denied')->latest('id')->firstOrFail();
        $this->assertSame($editor->id, $event->user_id);
        $this->assertSame('system-health.view', $event->details['permission']);
        $this->assertStringNotContainsString('Audit log', $this->get('/admin')->getContent(), 'the sidebar hides what the user cannot open');

        Role::findByName('Editor')->givePermissionTo(Permission::findByName('about.view'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->get('/admin/about')->assertOk();
    }

    public function test_every_log_screen_renders_filters_and_exports(): void
    {
        $admin = $this->boss();
        $this->actingAs($admin)->post('/admin/login', ['username' => 'boss', 'password' => 'bad']);
        SecurityEvent::create(['type' => 'login_failed', 'severity' => 'warning', 'message' => 'Marker =cmd', 'identifier' => '=HYPERLINK("x")', 'ip_address' => '198.51.100.7']);
        ActivityLog::create(['log' => 'activity', 'event' => 'settings_updated', 'description' => 'Saved site settings', 'user_id' => $admin->id, 'user_name' => 'The Boss']);

        $this->get('/admin/audit-log')->assertOk()->assertSee('Audit log');
        $this->get('/admin/activity-log?event=settings_updated')->assertOk()->assertSee('Saved site settings');
        $this->get('/admin/activity-log?event=nothing')->assertOk()->assertDontSee('Saved site settings');
        $this->get('/admin/security-events?severity=warning&ip=198.51.100')->assertOk()->assertSee('Marker =cmd');
        $this->get('/admin/security-events?from=garbage&to=also-bad')->assertOk();
        $this->get('/admin/audit-trail')->assertOk()->assertSee('Login history');

        $csv = $this->get('/admin/security-events?export=csv');
        $csv->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $body, 'cells starting with = are neutralised');
        $this->assertStringNotContainsString(',=HYPERLINK', $body);
    }

    public function test_system_health_and_about_pages_render(): void
    {
        $this->actingAs($this->boss());

        $this->get('/admin/system-health')->assertOk()->assertSee('Self-checks')->assertSee('Scheduler')->assertSee('Disk space');
        $this->get('/admin/about')->assertOk()->assertSee('Version')->assertSee('Laravel');
        $this->get('/admin')->assertOk()->assertSee('Security')->assertSee('System health')->assertSee('Failed sign-ins');
    }

    public function test_pruning_deletes_only_old_rows(): void
    {
        ActivityLog::create(['log' => 'activity', 'event' => 'login', 'description' => 'old', 'created_at' => now()->subDays(400)]);
        ActivityLog::create(['log' => 'activity', 'event' => 'login', 'description' => 'new']);
        ActivityLog::create(['log' => 'audit', 'event' => 'created', 'description' => 'audit 400d', 'created_at' => now()->subDays(400)]);
        SecurityEvent::create(['type' => 'login_failed', 'severity' => 'warning', 'message' => 'old', 'created_at' => now()->subDays(400)]);

        $this->artisan('logs:prune')->assertSuccessful();

        $this->assertSame(['audit 400d', 'new'], ActivityLog::orderBy('id')->pluck('description')->sort()->values()->all());
        $this->assertSame(0, SecurityEvent::count());
    }

    public function test_dashboard_and_sidebar_only_show_what_the_user_may_use(): void
    {
        PermissionCatalog::sync();
        Role::findOrCreate('Editor', 'web')->syncPermissions(['content.view', 'content.create']);
        $editor = User::factory()->role('Editor')->create(['username' => 'ed', 'email' => 'ed@example.com', 'full_name' => 'Edith Editor']);

        $page = $this->actingAs($editor)->get('/admin')->assertOk()
            ->assertSee('Edith')->assertSee('New content')->assertSee('Find a page')
            ->assertDontSee('Failed sign-ins')->assertDontSee('Recent activity')->assertDontSee('All systems working')
            ->assertDontSee('Upload document')->assertDontSee('System health');
        $this->assertStringContainsString('Editor', $page->getContent(), 'the role is shown in the user card');

        $this->actingAs($this->boss())->get('/admin')->assertOk()
            ->assertSee('Failed sign-ins')->assertSee('Recent activity')->assertSee('Upload document');
    }
}
