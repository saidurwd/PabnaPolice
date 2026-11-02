<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GroupType;
use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\User;
use App\Support\AdminLogin;
use App\Support\LegacyPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Auth::check() ? redirect()->route('admin.dashboard') : view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string', 'password' => 'required|string']);
        $key = 'admin-login:'.strtolower($data['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $user = User::where(str_contains($data['username'], '@') ? 'email' : 'username', $data['username'])->first();

        if (! $user || ! LegacyPassword::check($data['password'], (string) $user->password)) {
            RateLimiter::hit($key);
            throw ValidationException::withMessages(['username' => 'Incorrect username or password.']);
        }

        $statusError = [2 => 'This account is not activated.', 3 => 'This account is blocked.', 4 => 'This account has expired.'];
        if ((int) $user->status_id !== 1) {
            throw ValidationException::withMessages(['username' => $statusError[(int) $user->status_id] ?? 'This account is not allowed to sign in.']);
        }
        if ($user->group?->group_type !== GroupType::Back) {
            throw ValidationException::withMessages(['username' => 'This account has no access to the admin panel.']);
        }

        RateLimiter::clear($key);

        if (LegacyPassword::needsUpgrade($user->password)) {
            $user->password = LegacyPassword::make($data['password']);
            $user->save();
        }

        if ($user->hasTwoFactorEnabled()) {
            // Password is right; hold the login until the authenticator code is verified.
            $request->session()->put('two_factor.id', $user->id);
            $request->session()->put('two_factor.remember', $request->boolean('remember'));

            return redirect()->route('admin.two-factor');
        }

        AdminLogin::complete($request, $user, $request->boolean('remember'));

        return redirect()->intended(route('admin.dashboard'))->with('success', 'Welcome back, '.$user->full_name.'.');
    }

    public function logout(Request $request)
    {
        if ($id = Auth::id()) {
            $last = AuditTrail::where('user_id', $id)->orderByDesc('login_time')->orderByDesc('id')->first();
            $last?->update(['logout_time' => now()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been signed out.');
    }
}
