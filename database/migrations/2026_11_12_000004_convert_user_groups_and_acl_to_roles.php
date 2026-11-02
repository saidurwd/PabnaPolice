<?php

use App\Models\User;
use App\Support\BreakingMigration;
use App\Support\PermissionCatalog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Replaces the hand-built access control (user groups + the acl_* tables) with Laravel roles and permissions
 * (spatie/laravel-permission).
 *
 *  - every Back-office user group becomes a role of the same name ("Super Users" stays the all-powerful role)
 *  - every (module, action) the old rules allowed becomes the matching "<module>.<ability>" permission on that role
 *    (admin -> view, create -> create, update/edit -> update, delete -> delete)
 *  - every user keeps access through a role instead of users.group_id
 *
 * BEFORE any old data is dropped, the migration checks, for every user and every permission, that the new system
 * allows exactly what the old rules allowed, and aborts if not. The old tables are dumped to
 * storage/app/private/removed-tables/ first.
 */
return new class extends BreakingMigration
{
    /** Old module name (lower-cased: the old rows mix "Country" and "country") => new module slug. */
    private const MODULES = [
        'content' => 'content', 'category' => 'category', 'banner' => 'banner', 'bannercategory' => 'banner-category',
        'document' => 'document', 'documentcategory' => 'document-category', 'menu' => 'menu', 'user' => 'user',
        'userstatus' => 'user-status', 'audittrail' => 'audit-trail', 'country' => 'country', 'state' => 'state',
        'city' => 'city', 'district' => 'district', 'thana' => 'thana', 'sitelink' => 'site-link', 'feedback' => 'feedback',
        'setting' => 'settings', 'usergroup' => 'role',
    ];

    /** Old modules that deliberately have no equivalent (the permission editors themselves, and the removed modules). */
    private const DROPPED_MODULES = ['aclcontroller', 'aclaction', 'visitor', 'customer'];

    /** Old actions that only existed on removed screens. */
    private const DROPPED_ACTIONS = ['truncate'];

    private const ACTIONS = [
        'admin' => 'view', 'view' => 'view', 'create' => 'create', 'update' => 'update', 'edit' => 'update', 'delete' => 'delete',
        // the old group screen's extra actions all meant "change what this group may do"
        'access' => 'update', 'accessall' => 'update', 'accessallc' => 'update', 'turnon' => 'update', 'turnoff' => 'update',
    ];

    public function up(): void
    {
        $this->ensureYiiRetired();

        if (! Schema::hasTable('user_groups')) {
            return;   // already converted
        }

        $groups = DB::table('user_groups')->where('group_type', 'Back')->orderBy('id')->get();
        $rules = DB::table('acl_rules')->get();
        $userGroups = DB::table('users')->whereNotNull('group_id')->pluck('group_id', 'id');
        $before = $this->rowCounts(['users']);

        $this->backupTables(
            array_values(array_filter(['user_groups', 'acl_rules', 'acl_actions', 'acl_controllers'], fn ($t) => Schema::hasTable($t))),
            'access-control',
            ['users_group_id' => $userGroups->all()]
        );

        PermissionCatalog::sync();
        // Always exists, even on a fresh install with no groups: it is the role that can do everything.
        Role::findOrCreate(config('admin.super_role'), 'web')->syncPermissions(PermissionCatalog::names());

        // --- roles + their permissions -------------------------------------------------------------------------
        $roleByGroup = [];
        $expected = [];   // group id => [permission name => true]
        foreach ($groups as $group) {
            $name = (int) $group->id === 1 ? config('admin.super_role') : $group->title;
            $roleByGroup[$group->id] = Role::findOrCreate($name, 'web');
            $expected[$group->id] = [];
        }

        $unmapped = [];
        foreach ($rules as $rule) {
            if ((int) $rule->access !== 1 || ! isset($expected[$rule->group_id])) {
                continue;
            }
            $controller = strtolower((string) $rule->controller);
            $action = strtolower((string) $rule->actions);
            $module = self::MODULES[$controller] ?? null;
            $ability = self::ACTIONS[$action] ?? null;

            if ($module && $ability) {
                // A known module and a known kind of action. If that screen simply has no such action (settings can't be
                // "created", feedback can't be "created"), there is nothing to carry over and nothing is lost.
                if (in_array("{$module}.{$ability}", PermissionCatalog::names(), true)) {
                    $expected[$rule->group_id]["{$module}.{$ability}"] = true;
                }
            } elseif (! in_array($controller, self::DROPPED_MODULES, true) && ! in_array($action, self::DROPPED_ACTIONS, true)) {
                $unmapped[] = "group {$rule->group_id}: {$rule->controller}.{$rule->actions}";
            }
        }
        // An allowed old rule that maps to nothing (and is not a known-removed screen) would silently take access away.
        if ($unmapped) {
            throw new RuntimeException("Old permission rules with no new equivalent; nothing was dropped:\n - ".implode("\n - ", array_slice(array_unique($unmapped), 0, 20)));
        }

        foreach ($roleByGroup as $groupId => $role) {
            $role->syncPermissions((int) $groupId === 1 ? PermissionCatalog::names() : array_keys($expected[$groupId]));
        }

        // --- users -> roles ------------------------------------------------------------------------------------
        foreach ($userGroups as $userId => $groupId) {
            if (isset($roleByGroup[$groupId])) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roleByGroup[$groupId]->id, 'model_type' => User::class, 'model_id' => $userId,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // --- prove equivalence BEFORE deleting anything ---------------------------------------------------------
        $problems = [];
        foreach ($userGroups as $userId => $groupId) {
            if (! isset($roleByGroup[$groupId])) {
                continue;   // Front-office group: never had admin access
            }
            $user = User::find($userId);
            if ($user->roles()->count() !== 1) {
                $problems[] = "user {$userId} does not have exactly one role";
            }
            if ((int) $groupId === 1) {
                if (! $user->isSuperUser()) {
                    $problems[] = "user {$userId} lost super-user status";
                }

                continue;
            }
            foreach (PermissionCatalog::names() as $permission) {
                $wasAllowed = isset($expected[$groupId][$permission]);
                if ($user->hasPermissionTo($permission) !== $wasAllowed) {
                    $problems[] = "user {$userId} / {$permission}: old rules ".($wasAllowed ? 'allowed' : 'denied').' it, new roles disagree';
                }
            }
        }
        if ($problems) {
            throw new RuntimeException("Roles do not match the old access rules; nothing was dropped:\n - ".implode("\n - ", array_slice($problems, 0, 15)));
        }

        // --- retire the old structures -------------------------------------------------------------------------
        $this->withRelaxedSqlMode(function () {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            });
        });

        Schema::dropIfExists('acl_rules');
        Schema::dropIfExists('acl_actions');
        Schema::dropIfExists('acl_controllers');
        Schema::dropIfExists('user_groups');

        $this->assertSameRowCounts($before);
    }

    public function down(): void
    {
        // Not reversible on purpose: import the dump in storage/app/private/removed-tables/ to bring the old tables back.
    }
};
