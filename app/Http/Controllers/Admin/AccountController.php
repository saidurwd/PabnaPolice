<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\LegacyPassword;
use App\Support\Logs\Activity;
use App\Support\Logs\Security;
use App\Support\TwoFactor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/** "My account": profile, own password and two-factor authentication. Open to every signed-in admin user. */
class AccountController extends Controller
{
    public function edit(Request $request, TwoFactor $twoFactor)
    {
        $user = $request->user();
        $pending = ! $user->hasTwoFactorEnabled() && $user->two_factor_secret;

        return view('admin.account', [
            'user' => $user,
            'qr' => $pending ? $twoFactor->qrSvg($user->email, (string) $user->two_factor_secret) : null,
            'secret' => $pending ? $user->two_factor_secret : null,
            'recoveryCodes' => $request->session()->get('recovery_codes'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);
        Activity::record('profile_updated', 'Updated own profile');

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();
        $this->requirePassword($request->input('current_password'), $user->password, 'current_password');

        $user->password = LegacyPassword::make($request->input('password'));
        $user->save();
        Security::record('password_changed', Security::INFO, 'Password changed from the account page', $user->id, $user->username);
        Activity::record('password_changed', 'Changed own password');

        return back()->with('success', 'Password changed.');
    }

    public function startTwoFactor(Request $request, TwoFactor $twoFactor)
    {
        $user = $request->user();
        abort_if($user->hasTwoFactorEnabled(), 422);

        $user->two_factor_secret = $twoFactor->newSecret();
        $user->two_factor_confirmed_at = null;
        $user->save();

        return back();
    }

    public function confirmTwoFactor(Request $request, TwoFactor $twoFactor)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();

        if ($user->hasTwoFactorEnabled() || ! $user->two_factor_secret
            || ! $twoFactor->verify((string) $user->two_factor_secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'That code is not valid. Check the time on your phone and try again.']);
        }

        $codes = $twoFactor->recoveryCodes();
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $codes;
        $user->save();
        Security::record('two_factor_enabled', Security::INFO, 'Two-factor authentication turned on', $user->id, $user->username);

        return back()->with('success', 'Two-factor authentication is on.')->with('recovery_codes', $codes);
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactor $twoFactor)
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 422);
        $this->requirePassword($request->input('password'), $user->password, 'password');

        $codes = $twoFactor->recoveryCodes();
        $user->two_factor_recovery_codes = $codes;
        $user->save();
        Security::record('two_factor_enabled', Security::INFO, 'Two-factor recovery codes regenerated', $user->id, $user->username);

        return back()->with('success', 'New recovery codes created. The old ones no longer work.')->with('recovery_codes', $codes);
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();
        $this->requirePassword($request->input('password'), $user->password, 'password');

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();
        Security::record('two_factor_disabled', Security::WARNING, 'Two-factor authentication turned off', $user->id, $user->username);

        return back()->with('success', 'Two-factor authentication is off.');
    }

    private function requirePassword(string $plain, string $stored, string $field): void
    {
        if (! LegacyPassword::check($plain, $stored)) {
            throw ValidationException::withMessages([$field => 'That password is not correct.']);
        }
    }
}
