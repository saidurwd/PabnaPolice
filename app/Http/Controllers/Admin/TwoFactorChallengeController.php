<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminLogin;
use App\Support\TwoFactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** Second step of signing in for users who turned on an authenticator app. */
class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        return $request->session()->has('two_factor.id')
            ? view('admin.auth.two-factor')
            : redirect()->route('admin.login');
    }

    public function verify(Request $request, TwoFactor $twoFactor)
    {
        $user = User::find($request->session()->get('two_factor.id'));
        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.login');
        }

        $request->validate(['code' => 'nullable|string', 'recovery_code' => 'nullable|string']);

        $key = 'admin-2fa:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        $ok = false;
        if ($request->filled('recovery_code')) {
            $codes = $user->two_factor_recovery_codes ?? [];
            $given = strtolower(trim((string) $request->input('recovery_code')));
            $index = array_search($given, $codes, true);
            if ($index !== false) {
                unset($codes[$index]);                       // each recovery code works once
                $user->two_factor_recovery_codes = array_values($codes);
                $user->save();
                $ok = true;
            }
        } elseif ($request->filled('code')) {
            $ok = $twoFactor->verify((string) $user->two_factor_secret, (string) $request->input('code'));
        }

        if (! $ok) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['code' => 'That code is not valid.']);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('two_factor.remember', false);
        $request->session()->forget('two_factor.id');

        AdminLogin::complete($request, $user, $remember);

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Welcome back, '.$user->full_name.'.');
    }
}
