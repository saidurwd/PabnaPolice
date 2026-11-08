<?php

namespace App\Support;

use App\Admin\ResourceRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Every permission the admin panel knows about, named "<module>.<ability>" (content.view, banner-category.update, ...).
 * The modules come from the screens in ResourceRegistry plus the two custom pages (site settings, roles), so adding a
 * screen automatically adds its permissions the next time `php artisan permissions:sync` runs (the deploy script does).
 */
class PermissionCatalog
{
    public const ABILITIES = ['view', 'create', 'update', 'delete'];

    /** Read-only admin pages that are not list/edit screens: slug => label and route name (used by the sidebar). */
    public const PAGES = [
        'audit-log' => ['label' => 'Audit log', 'route' => 'admin.audit-log'],
        'activity-log' => ['label' => 'Activity log', 'route' => 'admin.activity-log'],
        'security-events' => ['label' => 'Security events', 'route' => 'admin.security-events'],
        'system-health' => ['label' => 'System health', 'route' => 'admin.system-health'],
        'about' => ['label' => 'About', 'route' => 'admin.about'],
    ];

    private const ACTION_TO_ABILITY = ['index' => 'view', 'create' => 'create', 'edit' => 'update', 'destroy' => 'delete'];

    /** @return array<string, array{label: string, abilities: array<int, string>}> module slug => details, in display order */
    public static function modules(): array
    {
        $modules = [];

        foreach (ResourceRegistry::all() as $slug => $def) {
            $abilities = array_values(array_unique(array_map(fn ($a) => self::ACTION_TO_ABILITY[$a], $def['actions'])));
            $modules[$slug] = ['label' => $def['plural'], 'abilities' => $abilities];
        }

        $modules['settings'] = ['label' => 'Site settings', 'abilities' => ['view', 'update']];
        $modules['role'] = ['label' => 'Roles & permissions', 'abilities' => ['view', 'create', 'update', 'delete']];

        foreach (self::PAGES as $slug => $page) {
            $modules[$slug] = ['label' => $page['label'], 'abilities' => ['view']];
        }

        return $modules;
    }

    /** @return array<int, string> */
    public static function names(): array
    {
        $names = [];
        foreach (self::modules() as $slug => $module) {
            foreach ($module['abilities'] as $ability) {
                $names[] = "{$slug}.{$ability}";
            }
        }

        return $names;
    }

    /** Creates the permissions that don't exist yet. Returns how many were created. */
    public static function sync(): int
    {
        $created = 0;
        foreach (self::names() as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            $created += $permission->wasRecentlyCreated ? 1 : 0;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $created;
    }
}
