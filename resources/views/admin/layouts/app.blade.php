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
@php($menu = \App\Support\AdminMenu::tree(auth()->user()))
<aside class="sidebar" id="sidebar">
    <a class="brand" href="{{ route('admin.dashboard') }}"><i class="bi bi-shield-check"></i><span>{{ setting('site_name') }}<br><small class="fw-normal opacity-75">Admin panel</small></span></a>
    <nav class="nav flex-column py-2">
        @foreach ($menu as $i => $item)
            @if (isset($item['heading']))
                <div class="nav-heading">{{ $item['heading'] }}</div>
            @elseif ($item['children'])
                <a class="nav-link {{ $item['open'] ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#m{{ $i }}" aria-expanded="{{ $item['open'] ? 'true' : 'false' }}">
                    <i class="bi {{ $item['icon'] }}"></i><span>{{ $item['title'] }}</span><i class="bi bi-chevron-right chev"></i>
                </a>
                <div class="collapse sub {{ $item['open'] ? 'show' : '' }}" id="m{{ $i }}">
                    @foreach ($item['children'] as $c)
                        <a class="nav-link {{ $c['active'] ? 'active' : '' }}" href="{{ $c['url'] }}">{{ $c['title'] }}@if ($c['badge'])<span class="badge rounded-pill text-bg-danger ms-auto">{{ $c['badge'] }}</span>@endif</a>
                    @endforeach
                </div>
            @else
                <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}"><i class="bi {{ $item['icon'] }}"></i><span>{{ $item['title'] }}</span>@if ($item['badge'])<span class="badge rounded-pill text-bg-danger ms-auto">{{ $item['badge'] }}</span>@endif</a>
            @endif
        @endforeach
        <a class="nav-link mt-3" href="{{ url('/') }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i><span>View website</span></a>
    </nav>
</aside>
<div class="main">
    <header class="topbar">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('show')"><i class="bi bi-list"></i></button>
        <h1 class="h5 mb-0">@yield('title', 'Dashboard')</h1>
        <div class="ms-auto d-flex align-items-center gap-3">
            <a class="text-muted text-decoration-none d-none d-md-inline" href="{{ route('admin.account') }}"><i class="bi bi-person-circle"></i> {{ auth()->user()->full_name }}</a>
            <form method="post" action="{{ route('admin.logout') }}">@csrf
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Sign out</button>
            </form>
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
