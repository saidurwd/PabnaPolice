<?php

namespace App\Http\Middleware;

use App\Admin\ResourceRegistry;
use Closure;
use Illuminate\Http\Request;

/**
 * Checks the signed-in user's permission for the current admin route.
 * Resource screens need "<slug>.<ability>" (content.view, banner.update, ...); special pages declare their permission
 * with ->defaults('permission', 'settings.update'). Super users pass via Gate::before.
 */
class AuthorizeResource
{
    private const ABILITY = [
        'index' => 'view', 'show' => 'view', 'create' => 'create', 'store' => 'create',
        'edit' => 'update', 'update' => 'update', 'restore' => 'update', 'destroy' => 'delete',
    ];

    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $permission = $route->defaults['permission'] ?? null;

        if (! $permission) {
            $def = ResourceRegistry::find((string) $route->parameter('resource'));
            abort_if($def === null, 404);
            $permission = $def['slug'].'.'.(self::ABILITY[$route->getActionMethod()] ?? 'view');
            $request->attributes->set('resource', $def);
        }

        abort_unless($request->user()?->can($permission), 403);

        return $next($request);
    }
}
