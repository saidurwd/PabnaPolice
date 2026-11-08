<?php

namespace App\Support;

use App\Admin\ResourceRegistry;
use App\Models\Feedback;
use App\Models\Menu;

/**
 * Builds the admin sidebar from the `menus` table, hiding what the user has no access to.
 *
 * Top-level rows carry a section heading in their `group` column ("Content", "Website", "Administration"); a heading is
 * only shown when at least one entry under it is visible. A row's `url` is a resource slug ("/user-group"), "/dashboard"
 * or "/setting"; the old Yii style ("/userGroup/admin") still resolves through the row's `controller` value.
 *
 * @phpstan-type Node array{title: string, url: ?string, icon: string, active: bool, badge: ?int, children: array<int, mixed>, open?: bool}
 */
class AdminMenu
{
    private const ICONS = [
        'fa-home' => 'bi-house', 'fa-cogs' => 'bi-gear', 'fa-lock' => 'bi-lock', 'fa-folder-open' => 'bi-folder2-open',
        'fa-camera' => 'bi-camera', 'fa-folder' => 'bi-folder', 'fa-user' => 'bi-person',
    ];

    /**
     * @return array<int, array{heading: string}|array<string, mixed>> headings and entries, in display order
     */
    public static function tree($user): array
    {
        $items = Menu::where('status', 1)->orderBy('ordering')->orderBy('id')->get();
        $out = [];
        $heading = null;

        foreach ($items->whereNull('parent_id') as $top) {
            $node = self::node($top, $user);
            $children = [];
            foreach ($items->where('parent_id', $top->id) as $child) {
                if ($c = self::node($child, $user)) {
                    $children[] = $c;
                }
            }

            if ($children) {
                $node = ($node ?? ['title' => $top->title, 'url' => null, 'icon' => self::icon($top->icon), 'active' => false, 'badge' => null]);
                $node['url'] = null;
                $node['children'] = $children;
                $node['open'] = (bool) array_filter($children, fn ($c) => $c['active']);
            } elseif (! $node || ! $node['url']) {
                continue;
            }

            $section = self::section($top->group);
            if ($section !== null && $section !== $heading) {
                $out[] = ['heading' => $section];
            }
            $heading = $section ?? $heading;
            $out[] = $node;
        }

        return $out;
    }

    /** Old rows hold a number or "1" in `group`; only real labels count as headings. */
    private static function section(?string $group): ?string
    {
        $group = trim((string) $group);

        return $group === '' || is_numeric($group) ? null : $group;
    }

    private static function node(Menu $m, $user): ?array
    {
        $path = trim($m->url, '/');
        $url = null;
        $badge = null;

        if (in_array($path, ['site/index', 'dashboard'], true)) {
            $url = route('admin.dashboard');
        } elseif (in_array($path, ['setting', 'setting/admin'], true)) {
            $url = $user->can('settings.view') ? route('admin.settings') : null;
        } elseif ($path === 'role') {
            $url = $user->can('role.view') ? route('admin.roles.index') : null;
        } elseif (isset(PermissionCatalog::PAGES[$path])) {
            $url = $user->can($path.'.view') ? route(PermissionCatalog::PAGES[$path]['route']) : null;
        } elseif ($def = ResourceRegistry::find($path)) {
            $url = $user->can($def['slug'].'.view') ? route('admin.resource.index', $def['slug']) : null;
            $badge = $url && $def['slug'] === 'feedback' ? (Feedback::where('is_read', false)->count() ?: null) : null;
        }

        if (! $url && $path !== '#') {
            return null;
        }

        return [
            'title' => $m->title,
            'url' => $url,
            'icon' => self::icon($m->icon),
            'active' => $url !== null && self::isActive($url),
            'badge' => $badge,
            'children' => [],
        ];
    }

    /** Active on the page itself and on its sub-pages (create/edit), but not on a sibling that merely shares a prefix. */
    private static function isActive(string $url): bool
    {
        $current = rtrim(request()->url(), '/');
        $url = rtrim($url, '/');

        return $current === $url || str_starts_with($current, $url.'/');
    }

    private static function icon(?string $legacy): string
    {
        if (! $legacy || $legacy === 'NULL') {
            return 'bi-circle';
        }
        if (str_contains($legacy, 'bi-')) {
            return $legacy;
        }
        foreach (self::ICONS as $fa => $bi) {
            if (str_contains($legacy, $fa)) {
                return $bi;
            }
        }

        return 'bi-circle';
    }
}
