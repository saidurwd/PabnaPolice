<?php

namespace App\Support;

use App\Admin\ResourceRegistry;
use App\Models\Menu;
use Illuminate\Support\Facades\Gate;

/** Builds the sidebar from the existing `menu` table, filtered by the user's ACL. */
class AdminMenu
{
    private const ICONS = [
        'fa-home' => 'bi-house', 'fa-cogs' => 'bi-gear', 'fa-lock' => 'bi-lock', 'fa-folder-open' => 'bi-folder2-open',
        'fa-camera' => 'bi-camera', 'fa-folder' => 'bi-folder', 'fa-user' => 'bi-person',
    ];

    public static function tree($user): array
    {
        $items = Menu::where('status', 1)->orderBy('ordering')->orderBy('id')->get();
        $tree = [];

        foreach ($items->whereNull('parent_id') as $top) {
            $node = self::node($top, $user);
            $children = [];
            foreach ($items->where('parent_id', $top->id) as $child) {
                if ($c = self::node($child, $user)) {
                    $children[] = $c;
                }
            }
            if ($children) {
                $node = ($node ?? ['title' => $top->title, 'url' => null, 'icon' => self::icon($top->icon), 'active' => false]);
                $node['url'] = null;
                $node['children'] = $children;
                $node['open'] = (bool) array_filter($children, fn ($c) => $c['active']);
                $tree[] = $node;
            } elseif ($node && $node['url']) {
                $tree[] = $node;
            }
        }

        return $tree;
    }

    private static function node(Menu $m, $user): ?array
    {
        $url = null;
        $path = trim($m->url, '/');

        if ($path === 'site/index') {
            $url = route('admin.dashboard');
        } elseif ($path === 'setting/admin') {
            $url = Gate::forUser($user)->allows('acl', ['setting', 'admin']) ? route('admin.settings') : null;
        } elseif ($def = ResourceRegistry::findByAcl($m->controller ?: explode('/', $path)[0])) {
            $url = Gate::forUser($user)->allows('acl', [$def['acl'], 'admin']) ? route('admin.resource.index', $def['slug']) : null;
        }

        if (! $url && $path !== '#' && $m->url !== '#') {
            return null;
        }

        return [
            'title' => $m->title,
            'url' => $url,
            'icon' => self::icon($m->icon),
            'active' => $url && rtrim(request()->url(), '/') !== '' && str_starts_with(request()->url(), $url) && $url !== route('admin.dashboard')
                || ($url === route('admin.dashboard') && request()->routeIs('admin.dashboard')),
            'children' => [],
        ];
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
