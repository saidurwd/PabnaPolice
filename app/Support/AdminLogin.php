<?php

namespace App\Support;

use App\Models\AuditTrail;
use App\Models\User;
use App\Support\Logs\Activity;
use App\Support\Logs\Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Final step of signing in (shared by the password form and the two-factor challenge). */
class AdminLogin
{
    public static function complete(Request $request, User $user, bool $remember): void
    {
        $ip = $request->ip();
        // A sign-in from an address this user has not used in the last 90 days is worth a line in Security events.
        $knownBefore = AuditTrail::where('user_id', $user->id)->exists();
        $seenIp = AuditTrail::where('user_id', $user->id)->where('ip_address', $ip)->where('login_time', '>=', now()->subDays(90))->exists();

        $user->last_login_at = now();
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        AuditTrail::create([
            'user_id' => $user->id,
            'login_time' => now(),
            'ip_address' => $ip,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
        ]);

        Activity::record('login', "{$user->full_name} signed in");
        if ($knownBefore && ! $seenIp) {
            Security::record('login_new_ip', Security::WARNING, "{$user->full_name} signed in from a new IP address ({$ip})", $user->id, $user->username);
        }
    }
}
