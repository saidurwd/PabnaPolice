<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') · {{ setting('site_name') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
<div class="login-wrap">
    <div class="card login-card"><div class="card-body p-4">
        <div class="text-center mb-4"><i class="bi bi-shield-check" style="font-size:2.6rem;color:#0b3d6b"></i>
            <h1 class="h5 mt-2 mb-0">{{ setting('site_name') }}</h1><small class="text-muted">Admin panel</small></div>
        @if (session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
        @yield('content')
    </div></div>
</div>
</body>
</html>
