<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ setting('site_name') }} Admin</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('head')
</head>
<body>
@php
    $menu = \App\Support\AdminMenu::tree(auth()->user());
    $me = auth()->user();
    $initials = collect(preg_split('/\s+/', trim($me->full_name ?: $me->username)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
    $roleName = $me->roles->pluck('name')->first() ?? 'Administrator';
    $crumbs = [];
    foreach ($menu as $it) {
        if (isset($it['heading'])) { continue; }
        foreach ($it['children'] as $c) {
            if ($c['active']) { $crumbs = [$it['title'], $c['title']]; break 2; }
        }
        if (! $it['children'] && $it['active']) { $crumbs = [$it['title']]; break; }
    }
@endphp
<div class="sb-backdrop" data-sidebar-close></div>
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <a class="sb-brand" href="{{ route('admin.dashboard') }}">
        <span class="sb-logo"><i class="bi bi-shield-check"></i></span>
        <span class="sb-brand-text"><strong>{{ setting('site_name') }}</strong><small>Admin panel</small></span>
    </a>
    <div class="sb-search"><i class="bi bi-search"></i><input type="search" id="sb-filter" placeholder="Find a page…" autocomplete="off" aria-label="Find a page"></div>
    <nav class="sb-nav" id="sb-nav">
        @foreach ($menu as $i => $item)
            @if (isset($item['heading']))
                <div class="sb-heading">{{ $item['heading'] }}</div>
            @elseif ($item['children'])
                <div class="sb-group">
                    <a class="sb-link {{ $item['open'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#m{{ $i }}" role="button" aria-expanded="{{ $item['open'] ? 'true' : 'false' }}">
                        <i class="bi {{ $item['icon'] }}"></i><span class="sb-text">{{ $item['title'] }}</span><i class="bi bi-chevron-right sb-chev"></i>
                    </a>
                    <div class="collapse sb-sub {{ $item['open'] ? 'show' : '' }}" id="m{{ $i }}">
                        @foreach ($item['children'] as $c)
                            <a class="sb-link {{ $c['active'] ? 'active' : '' }}" href="{{ $c['url'] }}" @if ($c['active']) aria-current="page" @endif>
                                <span class="sb-text">{{ $c['title'] }}</span>@if ($c['badge'])<span class="badge rounded-pill text-bg-danger ms-auto">{{ $c['badge'] }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a class="sb-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif>
                    <i class="bi {{ $item['icon'] }}"></i><span class="sb-text">{{ $item['title'] }}</span>@if ($item['badge'])<span class="badge rounded-pill text-bg-danger ms-auto">{{ $item['badge'] }}</span>@endif
                </a>
            @endif
        @endforeach
        <div class="sb-empty d-none">No page matches.</div>
    </nav>
    <div class="sb-user">
        <a class="sb-avatar" href="{{ route('admin.account') }}" title="My account">@if ($me->photoUrl())<img src="{{ $me->photoUrl() }}" alt="">@else{{ $initials }}@endif</a>
        <a class="sb-user-text" href="{{ route('admin.account') }}"><strong>{{ $me->full_name }}</strong><small>{{ $roleName }}</small></a>
        <form method="post" action="{{ route('admin.logout') }}">@csrf
            <button class="sb-signout" title="Sign out" aria-label="Sign out"><i class="bi bi-box-arrow-right"></i></button>
        </form>
    </div>
</aside>
<div class="main">
    <header class="topbar">
        <button class="btn btn-sm btn-light d-lg-none" data-sidebar-toggle aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
        <div class="topbar-title">
            @if ($crumbs)
                <nav class="crumbs" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Home</a>@foreach ($crumbs as $c)<i class="bi bi-chevron-right"></i><span>{{ $c }}</span>@endforeach
                </nav>
            @endif
            <h1 class="h5 mb-0">@yield('title', 'Dashboard')</h1>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a class="btn btn-sm btn-light d-none d-md-inline-flex align-items-center gap-1" href="{{ url('/') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> View website</a>
            <div class="dropdown">
                <button class="user-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <span class="avatar-sm">@if ($me->photoUrl())<img src="{{ $me->photoUrl() }}" alt="">@else{{ $initials }}@endif</span>
                    <span class="d-none d-md-block text-start lh-sm"><strong>{{ $me->full_name }}</strong><small>{{ $roleName }}</small></span>
                    <i class="bi bi-chevron-down small text-muted d-none d-md-block"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="{{ route('admin.account') }}"><i class="bi bi-person-gear me-2"></i>My account &amp; security</a></li>
                    <li><a class="dropdown-item" href="{{ url('/') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2"></i>View website</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="post" action="{{ route('admin.logout') }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
                </ul>
            </div>
        </div>
    </header>
    <main class="content">
        @foreach (['success', 'error'] as $type)
            @if (session($type))
                <div class="alert alert-{{ $type === 'error' ? 'danger' : 'success' }} alert-dismissible fade show">{{ session($type) }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
        @endforeach
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
