<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Security & audit features:
 *  - activity_logs   : one table for the Audit log (data changes with old/new values) and the Activity log (what users did)
 *  - security_events : failed sign-ins, lock-outs, password / two-factor / role changes, permission denials
 *  - audit_trails    : (shown as "Login history") gains the IP address and browser of each sign-in
 * plus the sidebar entries "Security" and "System" and their permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->string('log', 20)->index();                 // "audit" (data changes) or "activity" (user actions)
            $t->string('event', 60)->index();               // created, updated, deleted, restored, login, logout, ...
            $t->unsignedInteger('user_id')->nullable()->index();
            $t->string('user_name', 150)->nullable();       // kept so the entry stays readable after the user is deleted
            $t->string('subject_type', 100)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('subject_label', 255)->nullable();
            $t->string('description', 500);
            $t->json('properties')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('security_events', function (Blueprint $t) {
            $t->id();
            $t->string('type', 60)->index();
            $t->string('severity', 10)->index();            // info, warning, critical
            $t->unsignedInteger('user_id')->nullable()->index();
            $t->string('identifier', 150)->nullable();      // the username / e-mail that was typed in
            $t->string('message', 500);
            $t->json('details')->nullable();
            $t->string('ip_address', 45)->nullable()->index();
            $t->string('user_agent', 255)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });

        Schema::table('audit_trails', function (Blueprint $t) {
            $t->string('ip_address', 45)->nullable()->after('user_id');
            $t->string('user_agent', 255)->nullable()->after('ip_address');
        });

        $this->menu();
        $this->permissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('security_events');
        Schema::table('audit_trails', fn (Blueprint $t) => $t->dropColumn(['ip_address', 'user_agent']));

        DB::table('menus')->whereIn('url', ['/audit-log', '/activity-log', '/security-events', '/system-health', '/about'])->delete();
        DB::table('menus')->whereIn('title', ['Security', 'System'])->whereNull('parent_id')->delete();
    }

    private function menu(): void
    {
        $parent = fn (string $title, string $icon, int $order) => DB::table('menus')->insertGetId([
            'parent_id' => null, 'title' => $title, 'controller' => null, 'url' => '#', 'icon' => $icon,
            'ordering' => $order, 'status' => 1, 'group' => 'Administration',
        ]);
        $child = fn (int $parentId, string $title, string $url, int $order) => DB::table('menus')->insert([
            'parent_id' => $parentId, 'title' => $title, 'controller' => null, 'url' => $url, 'icon' => null,
            'ordering' => $order, 'status' => 1, 'group' => null,
        ]);

        $security = $parent('Security', 'bi-shield-lock', 70);
        $child($security, 'Audit log', '/audit-log', 1);
        $child($security, 'Activity log', '/activity-log', 2);
        $child($security, 'Security events', '/security-events', 3);

        // The old "Sign-in history" entry becomes "Login history" inside Security.
        $login = DB::table('menus')->where('url', '/audit-trail')->first();
        if ($login) {
            DB::table('menus')->where('id', $login->id)->update(['parent_id' => $security, 'title' => 'Login history', 'ordering' => 4]);
        } else {
            $child($security, 'Login history', '/audit-trail', 4);
        }

        $system = $parent('System', 'bi-hdd-stack', 90);
        $child($system, 'System health', '/system-health', 1);
        $child($system, 'About', '/about', 2);
    }

    private function permissions(): void
    {
        PermissionCatalog::sync();
        Role::findOrCreate(config('admin.super_role'), 'web')->syncPermissions(PermissionCatalog::names());
    }
};
