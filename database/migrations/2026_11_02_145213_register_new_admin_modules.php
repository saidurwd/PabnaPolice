<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registers the new admin modules in the existing menu / ACL tables (idempotent, additive).
 * Super Users (1) and Administrators (2) get full access, Managers (3) none.
 */
return new class extends Migration
{
    private array $modules = [
        ['controller' => 'setting', 'title' => 'Site Settings', 'url' => '/setting/admin', 'ordering' => 8],
        ['controller' => 'siteLink', 'title' => 'Site Links', 'url' => '/siteLink/admin', 'ordering' => 9],
        ['controller' => 'feedback', 'title' => 'Feedback', 'url' => '/feedback/admin', 'ordering' => 10],
    ];

    private array $actions = ['admin' => 'Manage', 'create' => 'New', 'update' => 'Edit', 'delete' => 'Delete'];

    public function up(): void
    {
        $parent = (int) DB::table('menu')->where('title', 'Configuration')->where('parent', 0)->value('id');

        foreach ($this->modules as $m) {
            $controllerId = DB::table('acl_controller')->where('controller', $m['controller'])->value('id')
                ?: DB::table('acl_controller')->insertGetId([
                    'controller' => $m['controller'], 'title' => $m['title'], 'controller_type' => 1, 'status' => 1,
                ]);

            foreach ($this->actions as $action => $title) {
                if (! DB::table('acl_action')->where('controller_id', $controllerId)->where('action', $action)->exists()) {
                    DB::table('acl_action')->insert([
                        'controller_id' => $controllerId, 'title' => $title, 'action' => $action, 'controller_type' => 1,
                    ]);
                }

                foreach ([1 => 1, 2 => 1, 3 => 0] as $group => $access) {
                    // A fresh install has no groups yet; rules for a missing group would be orphans.
                    if (! DB::table('user_group')->where('id', $group)->exists()) {
                        continue;
                    }
                    $exists = DB::table('acl')->where(['group_id' => $group, 'controller' => $m['controller'], 'actions' => $action])->exists();
                    if (! $exists) {
                        DB::table('acl')->insert([
                            'group_id' => $group, 'controller' => $m['controller'], 'actions' => $action,
                            'action_title' => $title, 'access' => $access, 'controller_type' => 1,
                        ]);
                    }
                }
            }

            if (! DB::table('menu')->where('url', $m['url'])->exists()) {
                DB::table('menu')->insert([
                    'parent' => $parent, 'title' => $m['title'], 'controller' => $m['controller'],
                    'url' => $m['url'], 'ordering' => $m['ordering'], 'status' => 1, 'group' => '1',
                ]);
            }
        }
    }

    public function down(): void
    {
        // Left in place on purpose: removing ACL/menu rows could lock admins out.
    }
};
