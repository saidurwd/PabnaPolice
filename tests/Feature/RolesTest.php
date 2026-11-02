<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminMenu;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function superUser(): User
    {
        return User::factory()->superUser()->create(['username' => 'boss', 'email' => 'boss@example.com']);
    }

    public function test_catalog_covers_every_screen_and_sync_is_idempotent(): void
    {
        Permission::query()->delete();   // a fresh install already has them (the migrations create them)
        $names = PermissionCatalog::names();
        $this->assertContains('content.view', $names);
        $this->assertContains('banner-category.update', $names);
        $this->assertContains('settings.update', $names);
        $this->assertContains('role.delete', $names);
        $this->assertNotContains('audit-trail.create', $names, 'read-only screens only get the abilities they have');

        $this->assertSame(count($names), PermissionCatalog::sync());
        $this->assertSame(0, PermissionCatalog::sync());
        $this->assertSame(count($names), Permission::count());
    }

    public function test_a_role_can_be_created_with_permissions_and_assigned_to_a_user(): void
    {
        PermissionCatalog::sync();
        $admin = $this->actingAs($this->superUser());

        $admin->post('/admin/role', ['name' => 'Publisher', 'permissions' => ['content.view', 'content.create', 'content.update']])
            ->assertRedirect(route('admin.roles.index'));
        $role = Role::findByName('Publisher');
        $this->assertEqualsCanonicalizing(['content.view', 'content.create', 'content.update'], $role->permissions->pluck('name')->all());

        $admin->post('/admin/user', [
            'full_name' => 'Pat', 'username' => 'pat', 'email' => 'pat@example.com', 'password' => 'Secret123',
            'role' => 'Publisher', 'status_id' => 1,
        ])->assertRedirect();
        $pat = User::where('username', 'pat')->first();
        $this->assertSame('Publisher', $pat->role);

        auth()->logout();
        $this->actingAs($pat)->get('/admin/content')->assertOk();
        $this->actingAs($pat)->get('/admin/category')->assertForbidden();
        $this->actingAs($pat)->get('/admin/role')->assertForbidden();
    }

    public function test_changing_a_users_role_replaces_the_old_one(): void
    {
        PermissionCatalog::sync();
        Role::findOrCreate('Editor', 'web');
        Role::findOrCreate('Author', 'web');
        $user = User::factory()->role('Editor')->create(['username' => 'ed', 'email' => 'ed@example.com']);

        $this->actingAs($this->superUser())->put("/admin/user/{$user->id}", [
            'full_name' => 'Ed', 'username' => 'ed', 'email' => 'ed@example.com', 'role' => 'Author', 'status_id' => 1,
        ])->assertRedirect();

        $this->assertSame(['Author'], $user->fresh()->roles->pluck('name')->all());
    }

    public function test_the_role_must_exist(): void
    {
        $this->actingAs($this->superUser())->post('/admin/user', [
            'full_name' => 'X', 'username' => 'x', 'email' => 'x@example.com', 'password' => 'Secret123', 'role' => 'Nonexistent', 'status_id' => 1,
        ])->assertSessionHasErrors('role');
    }

    public function test_the_super_role_is_protected(): void
    {
        PermissionCatalog::sync();
        $boss = $this->superUser();
        $superId = Role::findByName(config('admin.super_role'))->id;
        $admin = $this->actingAs($boss);

        $admin->put("/admin/role/{$superId}", ['name' => 'Renamed', 'permissions' => []])->assertForbidden();
        $admin->delete("/admin/role/{$superId}")->assertSessionHas('error');
        $this->assertNotNull(Role::find($superId));

        // The last Super User can neither be deleted nor demoted.
        Role::findOrCreate('Editor', 'web');
        $admin->put("/admin/user/{$boss->id}", ['full_name' => 'Boss', 'username' => 'boss', 'email' => 'boss@example.com', 'role' => 'Editor', 'status_id' => 1])
            ->assertSessionHasErrors('role');
        $this->assertTrue($boss->fresh()->isSuperUser());

        $other = User::factory()->superUser()->create(['username' => 'boss2', 'email' => 'b2@example.com']);
        $admin->delete("/admin/user/{$other->id}")->assertSessionHas('success');   // not the last one any more
        $admin->delete("/admin/user/{$boss->id}")->assertSessionHas('error');      // yourself / the last one
    }

    public function test_a_role_with_users_cannot_be_deleted(): void
    {
        $role = Role::findOrCreate('Editor', 'web');
        User::factory()->role('Editor')->create(['username' => 'ed', 'email' => 'ed@example.com']);
        $admin = $this->actingAs($this->superUser());

        $admin->delete("/admin/role/{$role->id}")->assertSessionHas('error');
        $this->assertNotNull(Role::find($role->id));

        User::where('username', 'ed')->first()->removeRole('Editor');
        $admin->delete("/admin/role/{$role->id}")->assertSessionHas('success');
        $this->assertNull(Role::find($role->id));
    }

    public function test_the_sidebar_only_shows_what_the_role_may_open_and_is_sectioned(): void
    {
        PermissionCatalog::sync();
        Role::findOrCreate('Editor', 'web')->givePermissionTo(['content.view', 'document.view']);
        $editor = User::factory()->role('Editor')->create(['username' => 'ed', 'email' => 'ed@example.com']);
        $boss = $this->superUser();

        DB::table('menus')->whereNotNull('parent_id')->delete();   // a fresh install already has the real menu
        DB::table('menus')->delete();
        DB::table('menus')->insert([
            ['id' => 1, 'parent_id' => null, 'title' => 'Content', 'url' => '#', 'ordering' => 10, 'status' => 1, 'group' => 'Content'],
            ['id' => 2, 'parent_id' => 1, 'title' => 'Content', 'url' => '/content', 'ordering' => 1, 'status' => 1, 'group' => null],
            ['id' => 3, 'parent_id' => 1, 'title' => 'Categories', 'url' => '/category', 'ordering' => 2, 'status' => 1, 'group' => null],
            ['id' => 4, 'parent_id' => null, 'title' => 'Users & access', 'url' => '#', 'ordering' => 60, 'status' => 1, 'group' => 'Administration'],
            ['id' => 5, 'parent_id' => 4, 'title' => 'Users', 'url' => '/user', 'ordering' => 1, 'status' => 1, 'group' => null],
        ]);

        $titles = fn ($tree) => collect($tree)->map(fn ($n) => $n['heading'] ?? $n['title'])->all();

        $this->actingAs($editor);
        $tree = AdminMenu::tree($editor);
        $this->assertSame(['Content', 'Content'], $titles($tree), 'one heading, one group; Administration is hidden entirely');
        $this->assertSame(['Content'], collect($tree[1]['children'])->pluck('title')->all(), 'Categories is hidden: no category.view');

        $this->actingAs($boss);
        $this->assertSame(['Content', 'Content', 'Administration', 'Users & access'], $titles(AdminMenu::tree($boss)));
    }

    public function test_deleted_permissions_are_recreated_by_sync(): void
    {
        PermissionCatalog::sync();
        Permission::findByName('content.view')->delete();

        $this->artisan('permissions:sync')->assertSuccessful();
        $this->assertNotNull(Permission::findByName('content.view'));
    }
}
