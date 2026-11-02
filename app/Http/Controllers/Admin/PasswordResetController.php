<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LegacyPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('admin.auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Same answer whether or not the address exists, so the form can't be used to find accounts.
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'If that address belongs to an account, a password reset link has been sent to it.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->password = LegacyPassword::make($password);
            $user->remember_token = Str::random(60);
            $user->save();

            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset
            ? redirect()->route('admin.login')->with('success', 'Your password has been reset. You can sign in now.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
