@extends('admin.layouts.guest')
@section('title', 'Forgot password')
@section('content')
<p class="text-muted">Enter the e-mail address of your account and we will send you a link to choose a new password.</p>
<form method="post" action="{{ route('admin.password.email') }}">@csrf
    <div class="mb-3"><label class="form-label" for="email">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <button class="btn btn-primary w-100">Send reset link</button>
    <div class="text-center mt-3"><a href="{{ route('admin.login') }}">Back to sign in</a></div>
</form>
@endsection
