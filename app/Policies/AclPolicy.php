<?php

namespace App\Policies;

use App\Models\Acl;
use App\Models\User;

/**
 * Backs the `acl` gate: `Gate::allows('acl', ['content', 'update'])` / `@can('acl', [...])`.
 *
 * Reads the existing `acl` table (group x module x action). Module names are compared
 * case-insensitively because the legacy rows mix cases ("Country", "userGroup").
 * Super Users (group 1) pass through Gate::before in AppServiceProvider. No row = no access.
 */
class AclPolicy
{
    /** @var array<int, array<string, bool>> */
    private static array $cache = [];

    public static function allows(?User $user, string $module, string $action): bool
    {
        if (! $user) {
            return false;
        }

        $group = (int) $user->group_id;
        self::$cache[$group] ??= Acl::where('group_id', $group)->where('access', 1)->get()
            ->mapWithKeys(fn (Acl $r) => [strtolower($r->controller).'|'.strtolower($r->actions) => true])->all();

        return isset(self::$cache[$group][strtolower($module).'|'.strtolower($action)]);
    }

    public static function forget(): void
    {
        self::$cache = [];
    }
}
