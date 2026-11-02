@extends('admin.layouts.guest')
@section('title', 'Two-factor verification')
@section('content')
<form method="post" action="{{ route('admin.two-factor') }}">@csrf
    <p class="text-muted">Open your authenticator app and enter the 6-digit code for this account.</p>
    <div class="mb-3"><label class="form-label" for="code">Authentication code</label>
        <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" class="form-control @error('code') is-invalid @enderror" autofocus>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <button class="btn btn-primary w-100">Verify</button>
    <details class="mt-3"><summary class="text-muted">Lost your phone? Use a recovery code</summary>
        <input name="recovery_code" class="form-control mt-2" placeholder="xxxxxxxx-xxxxxxxx" autocomplete="off"></details>
    <div class="text-center mt-3"><a href="{{ route('admin.login') }}">Cancel</a></div>
</form>
@endsection
