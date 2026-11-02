@extends('admin.layouts.app')
@section('title', 'My account')
@section('content')
@if ($recoveryCodes)
    <div class="alert alert-warning"><strong>Save these recovery codes now.</strong> Each can be used once if you lose your phone. They will not be shown again.
        <pre class="mb-0 mt-2 bg-white p-2 border rounded">{{ implode("\n", $recoveryCodes) }}</pre></div>
@endif
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3"><div class="card-header bg-white fw-semibold">Profile</div><div class="card-body">
            <form method="post" action="{{ route('admin.account.profile') }}">@csrf @method('PUT')
                <div class="mb-3"><label class="form-label" for="full_name">Full name</label>
                    <input id="full_name" name="full_name" value="{{ old('full_name', $user->full_name) }}" class="form-control @error('full_name') is-invalid @enderror" required>
                    @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label" for="email">E-mail</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                    <div class="form-text">Password reset links are sent here.</div>
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <button class="btn btn-primary">Save profile</button>
            </form>
        </div></div>

        <div class="card"><div class="card-header bg-white fw-semibold">Change password</div><div class="card-body">
            <form method="post" action="{{ route('admin.account.password') }}">@csrf @method('PUT')
                <div class="mb-3"><label class="form-label" for="current_password">Current password</label>
                    <input id="current_password" type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                    @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label" for="password">New password</label>
                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required>
                    <div class="form-text">At least 8 characters, with letters and numbers.</div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                <div class="mb-3"><label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
                <button class="btn btn-primary">Change password</button>
            </form>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card"><div class="card-header bg-white fw-semibold">Two-factor authentication
            @if ($user->hasTwoFactorEnabled())<span class="badge text-bg-success ms-2">On</span>@else<span class="badge text-bg-secondary ms-2">Off</span>@endif</div>
        <div class="card-body">
            @if ($user->hasTwoFactorEnabled())
                <p>Signing in asks for a code from your authenticator app as well as your password.</p>
                <form method="post" action="{{ route('admin.account.two-factor.recovery') }}" class="row g-2 mb-3">@csrf
                    <div class="col"><input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Your password" required></div>
                    <div class="col-auto"><button class="btn btn-outline-secondary">New recovery codes</button></div>
                </form>
                <form method="post" action="{{ route('admin.account.two-factor.disable') }}" class="row g-2" onsubmit="return confirm('Turn off two-factor authentication?')">@csrf @method('DELETE')
                    <div class="col"><input type="password" name="password" class="form-control" placeholder="Your password" required></div>
                    <div class="col-auto"><button class="btn btn-outline-danger">Turn off</button></div>
                </form>
                @error('password')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            @elseif ($qr)
                <p>1. Scan this code with an authenticator app (Google Authenticator, Microsoft Authenticator, Authy).</p>
                <div class="mb-2" style="max-width:220px">{!! $qr !!}</div>
                <p class="small text-muted">Or type this key in manually: <code>{{ $secret }}</code></p>
                <p>2. Enter the 6-digit code the app shows to finish.</p>
                <form method="post" action="{{ route('admin.account.two-factor.confirm') }}" class="row g-2">@csrf
                    <div class="col"><input name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control @error('code') is-invalid @enderror" placeholder="123456" required>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-auto"><button class="btn btn-primary">Turn on</button></div>
                </form>
            @else
                <p>Add a second step to signing in: a 6-digit code from an app on your phone. Recommended for everyone who can publish or manage users.</p>
                <form method="post" action="{{ route('admin.account.two-factor.start') }}">@csrf <button class="btn btn-primary">Set up</button></form>
            @endif
        </div></div>
    </div>
</div>
@endsection
