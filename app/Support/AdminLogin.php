<?php

namespace App\Support;

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Final step of signing in (shared by the password form and the two-factor challenge). */
class AdminLogin
{
    public static function complete(Request $request, User $user, bool $remember): void
    {
        $user->last_login_at = now();
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        AuditTrail::create(['user_id' => $user->id, 'login_time' => now()]);
    }
}
