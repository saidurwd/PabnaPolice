<?php

namespace Tests\Feature;

use App\Admin\ResourceRegistry;
use App\Models\Category;
use App\Models\Content;
use App\Models\Setting;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    /** A user with the given role (the super role by default). Roles are what allow someone into the admin panel. */
    private function user(?string $role = null, string $password = 'secret12'): User
    {
        return User::factory()->role($role ?? config('admin.super_role'))->create([
            'full_name' => 'User One', 'username' => 'user1', 'email' => 'u1@example.com', 'password' => sha1($password),
        ]);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/content')->assertRedirect(route('admin.login'));
    }

    public function test_legacy_sha1_password_can_log_in_and_is_not_rehashed_by_default(): void
    {
        $user = $this->user();

        $this->post('/admin/login', ['username' => 'user1', 'password' => 'secret12'])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(sha1('secret12'), $user->fresh()->password, 'old Yii admin must keep working until cutover');
        $this->assertDatabaseHas('audit_trails', ['user_id' => $user->id]);
    }

    public function test_password_is_upgraded_to_bcrypt_when_enabled(): void
    {
        config(['legacy.password_upgrade' => true]);
        $user = $this->user();

        $this->post('/admin/login', ['username' => 'user1', 'password' => 'secret12']);

        $this->assertStringStartsWith('$2y$', $user->fresh()->password);
    }

    public function test_wrong_password_and_blocked_users_are_rejected(): void
    {
        $this->user();
        $this->post('/admin/login', ['username' => 'user1', 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->assertGuest();

        DB::table('user_statuses')->insert(['id' => 3, 'title' => 'Blocked']);
        User::where('username', 'user1')->update(['status_id' => 3]);
        $this->post('/admin/login', ['username' => 'user1', 'password' => 'secret12'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_users_without_a_role_cannot_enter_the_admin_panel(): void
    {
        User::factory()->create(['username' => 'norole', 'email' => 'n@example.com', 'password' => sha1('secret12')]);

        $this->post('/admin/login', ['username' => 'norole', 'password' => 'secret12'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_permissions_control_access_and_default_to_deny(): void
    {
        PermissionCatalog::sync();
        $manager = $this->user('Manager');
        Role::findByName('Manager')->givePermissionTo(['content.view', 'content.update']);

        $this->actingAs($manager)->get('/admin/content')->assertOk();
        $this->actingAs($manager)->get('/admin/content/create')->assertForbidden();      // not granted
        $this->actingAs($manager)->post('/admin/content', [])->assertForbidden();
        $this->actingAs($manager)->delete('/admin/content/1')->assertForbidden();
        $this->actingAs($manager)->get('/admin/user')->assertForbidden();
        $this->actingAs($manager)->get('/admin/settings')->assertForbidden();
        $this->actingAs($manager)->get('/admin/role')->assertForbidden();

        // Granting a permission takes effect immediately.
        Role::findByName('Manager')->givePermissionTo('content.create');
        $this->actingAs($manager)->get('/admin/content/create')->assertOk();
    }

    public function test_super_user_reaches_every_screen(): void
    {
        $admin = $this->actingAs($this->user());

        foreach (array_keys(ResourceRegistry::all()) as $slug) {
            $admin->get("/admin/{$slug}")->assertOk();
        }
        $admin->get('/admin')->assertOk();
        $admin->get('/admin/settings')->assertOk();
        $admin->get('/admin/role')->assertOk();
        $admin->get('/admin/role/create')->assertOk();
    }

    public function test_editing_content_does_not_move_its_published_date(): void
    {
        $admin = $this->user();
        $cat = Category::factory()->create();
        $content = Content::factory()->create(['category_id' => $cat->id, 'published_at' => '2020-01-05 10:30:00']);

        $this->actingAs($admin)->put("/admin/content/{$content->id}", [
            'title' => 'New title', 'category_id' => $cat->id, 'status' => 'Active', 'published_at' => '',
        ])->assertRedirect();

        $fresh = $content->fresh();
        $this->assertSame('New title', $fresh->title);
        $this->assertSame('2020-01-05 10:30:00', (string) $fresh->getRawOriginal('published_at'));
    }

    public function test_category_tree_fields_are_rebuilt_and_cycles_rejected(): void
    {
        $admin = $this->actingAs($this->user());
        $admin->post('/admin/category', ['title' => 'A', 'status' => 'Active', 'parent_id' => '']);
        $a = Category::where('title', 'A')->first();
        $admin->post('/admin/category', ['title' => 'B', 'status' => 'Active', 'parent_id' => $a->id]);
        $b = Category::where('title', 'B')->first();

        $this->assertSame('A/B', $b->alias);
        $this->assertSame("0.{$a->id}.{$b->id}", $b->path);

        $admin->put("/admin/category/{$a->id}", ['title' => 'A', 'status' => 'Active', 'parent_id' => $b->id])->assertSessionHasErrors('parent_id');
        $admin->delete("/admin/category/{$a->id}")->assertSessionHas('error');
        $this->assertNotNull(Category::find($a->id));
    }

    public function test_settings_can_be_changed(): void
    {
        $this->actingAs($this->user())->put('/admin/settings', ['site_name' => 'My Police Site'])->assertRedirect();

        Setting::flush();
        $this->assertSame('My Police Site', setting('site_name'));
    }
}
