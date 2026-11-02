@extends('admin.layouts.guest')
@section('title', 'Choose a new password')
@section('content')
<form method="post" action="{{ route('admin.password.update') }}">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3"><label class="form-label" for="email">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="form-control @error('email') is-invalid @enderror" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="mb-3"><label class="form-label" for="password">New password</label>
        <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" required autofocus>
        <div class="form-text">At least 8 characters, with letters and numbers.</div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    <div class="mb-3"><label class="form-label" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
    <button class="btn btn-primary w-100">Reset password</button>
</form>
@endsection
