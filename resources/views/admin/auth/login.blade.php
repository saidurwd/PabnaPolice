<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · {{ setting('site_name') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
<div class="login-wrap">
    <div class="card login-card"><div class="card-body p-4">
        <div class="text-center mb-4"><i class="bi bi-shield-check" style="font-size:2.6rem;color:#0b3d6b"></i>
            <h1 class="h5 mt-2 mb-0">{{ setting('site_name') }}</h1><small class="text-muted">Admin panel</small></div>
        @if (session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        <form method="post" action="{{ route('admin.login') }}">@csrf
            <div class="mb-3"><label class="form-label">Username or email</label>
                <input name="username" value="{{ old('username') }}" class="form-control @error('username') is-invalid @enderror" autofocus required>
                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required></div>
            <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="r"><label class="form-check-label" for="r">Remember me</label></div>
            <button class="btn btn-primary w-100">Sign in</button>
            <div class="text-center mt-3"><a href="{{ route('admin.password.request') }}">Forgot your password?</a></div>
        </form>
    </div></div>
</div>
</body>
</html>
