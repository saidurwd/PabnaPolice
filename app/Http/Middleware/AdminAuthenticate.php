<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Status 1 = Active (see user_status). Blocked / expired accounts are signed out immediately.
        if (! $user || (int) $user->status_id !== 1) {
            Auth::logout();

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
