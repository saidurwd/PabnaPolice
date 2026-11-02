<?php

namespace App\Http\Middleware;

use App\Admin\ResourceRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Maps the current admin route to the legacy ACL (controller key + admin/create/update/delete).
 * Resource routes carry a {resource} slug; special pages set ->defaults('acl', ...).
 */
class AuthorizeResource
{
    private const MAP = [
        'index' => 'admin', 'show' => 'admin', 'create' => 'create', 'store' => 'create',
        'edit' => 'update', 'update' => 'update', 'destroy' => 'delete', 'restore' => 'update',
    ];

    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $aclKey = $route->defaults['acl'] ?? null;

        if (! $aclKey) {
            $def = ResourceRegistry::find((string) $route->parameter('resource'));
            abort_if($def === null, 404);
            $aclKey = $def['acl'];
            $request->attributes->set('resource', $def);
        }

        $action = $route->defaults['acl_action'] ?? (self::MAP[$route->getActionMethod()] ?? 'admin');

        abort_unless(Gate::forUser($request->user())->allows('acl', [$aclKey, $action]), 403);

        return $next($request);
    }
}
