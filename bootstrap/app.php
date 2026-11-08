<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\AuthorizeResource;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SecurityHeaders::class);
        $middleware->appendToGroup('web', ContentSecurityPolicy::class);
        // Browsers POST CSP violation reports without a CSRF token.
        $middleware->validateCsrfTokens(except: ['csp-report']);
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->alias([
            'admin.auth' => AdminAuthenticate::class,
            'admin.can' => AuthorizeResource::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
