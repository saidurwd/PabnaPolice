@extends('admin.layouts.app')
@section('title', 'Dashboard')
@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $healthUi = ['ok' => ['success', 'bi-check-lg', 'All systems working', 'Every self-check passed.'], 'warn' => ['warning', 'bi-exclamation-lg', 'Needs attention', 'Working, but a check raised a warning.'], 'fail' => ['danger', 'bi-x-lg', 'Something is broken', 'At least one check failed.']];
    $tones = ['created' => ['success', 'bi-plus-lg'], 'updated' => ['primary', 'bi-pencil'], 'trashed' => ['warning', 'bi-trash'], 'deleted' => ['danger', 'bi-trash3'], 'restored' => ['info', 'bi-arrow-counterclockwise'], 'login' => ['success', 'bi-box-arrow-in-right'], 'logout' => ['secondary', 'bi-box-arrow-left']];
    $maxCat = max(1, (int) ($categories->max('n') ?? 1));
@endphp
@section('content')
<div class="welcome">
    <div>
        <h2>{{ $greeting }}, {{ \Illuminate\Support\Str::of(auth()->user()->full_name)->before(' ') }}</h2>
        <p>{{ now()->format('l, j F Y') }} · here is what is happening on the website.</p>
    </div>
    @if ($quick)
        <div class="quick">
            @foreach ($quick as $i => [$label, $icon, $url])
                <a class="btn btn-sm {{ $i === 0 ? 'btn-primary' : 'btn-outline-secondary bg-white' }}" href="{{ $url }}"><i class="bi {{ $icon }}"></i>{{ $label }}</a>
            @endforeach
        </div>
    @endif
</div>

<div class="row g-3 mb-4">
    @foreach ($stats as [$label, $count, $icon, $tone, $slug, $hint])
        <div class="col-6 col-md-4 col-xl">
            <a class="kpi" href="{{ route('admin.resource.index', $slug) }}">
                <div class="kpi-top"><span class="kpi-label">{{ $label }}</span><span class="kpi-icon tone-{{ $tone }}"><i class="bi {{ $icon }}"></i></span></div>
                <div class="kpi-n">{{ number_format($count) }}</div>
                @if ($hint)<div class="kpi-hint">{{ $hint }}</div>@endif
            </a>
        </div>
    @endforeach
    @if ($failedSignIns !== null)
        <div class="col-6 col-md-4 col-xl">
            <a class="kpi" href="{{ route('admin.security-events', ['type' => 'login_failed']) }}">
                <div class="kpi-top"><span class="kpi-label">Failed sign-ins</span><span class="kpi-icon tone-{{ $failedSignIns ? 'danger' : 'success' }}"><i class="bi bi-shield-{{ $failedSignIns ? 'exclamation' : 'check' }}"></i></span></div>
                <div class="kpi-n">{{ number_format($failedSignIns) }}</div>
                <div class="kpi-hint">Last 24 hours</div>
            </a>
        </div>
    @endif
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header"><div class="panel-title">Latest content @can('content.view')<a href="{{ route('admin.resource.index', 'content') }}">View all</a>@endcan</div></div>
            @forelse ($recent as $c)
                <a class="row-item" href="{{ route('admin.resource.edit', ['content', $c->id]) }}">
                    <span class="dot tone-primary"><i class="bi bi-file-earmark-text"></i></span>
                    <span class="grow"><span class="t">{{ str_chars_limit($c->title, 90) }}</span><span class="s">{{ $c->category?->title ?? 'Uncategorised' }} · {{ $c->published_at?->format('d M Y') }}</span></span>
                    <span class="badge text-bg-{{ enum_value($c->status) === 'Active' ? 'success' : 'secondary' }}">{{ enum_value($c->status) }}</span>
                </a>
            @empty <div class="empty">No content yet.</div> @endforelse
        </div>

        @if ($categories->isNotEmpty())
        <div class="card">
            <div class="card-header"><div class="panel-title">Content by category</div></div>
            <div class="py-2">
                @foreach ($categories as $c)
                    <div class="bar-row"><div class="lbl"><span>{{ str_chars_limit($c['title'], 50) }}</span><span class="text-muted">{{ number_format($c['n']) }}</span></div>
                        <div class="progress"><div class="progress-bar" style="width: {{ round($c['n'] / $maxCat * 100) }}%"></div></div></div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="col-xl-4">
        @if ($health)
            @php([$ht, $hi, $hl, $hd] = $healthUi[$health])
            <a class="card mb-3 text-decoration-none text-reset d-block" href="{{ route('admin.system-health') }}">
                <div class="health"><span class="dot tone-{{ $ht }}"><i class="bi {{ $hi }}"></i></span>
                    <div class="flex-grow-1"><div class="fw-semibold">{{ $hl }}</div><div class="small text-muted">{{ $hd }}</div></div>
                    <i class="bi bi-chevron-right text-muted"></i></div>
            </a>
        @endif

        @if ($activity !== null)
        <div class="card mb-3">
            <div class="card-header"><div class="panel-title">Recent activity @can('activity-log.view')<a href="{{ route('admin.activity-log') }}">View all</a>@endcan</div></div>
            @forelse ($activity as $a)
                @php([$at, $ai] = $tones[$a->event] ?? ['secondary', 'bi-lightning'])
                <div class="row-item">
                    <span class="dot tone-{{ $at }}"><i class="bi {{ $ai }}"></i></span>
                    <span class="grow"><span class="t">{{ $a->description }}</span><span class="s">{{ $a->user_name ?: 'System' }} · {{ $a->created_at->diffForHumans() }}</span></span>
                </div>
            @empty <div class="empty">No activity recorded yet.</div> @endforelse
        </div>
        @endif

        <div class="card">
            <div class="card-header"><div class="panel-title">Recent sign-ins @can('audit-trail.view')<a href="{{ route('admin.resource.index', 'audit-trail') }}">View all</a>@endcan</div></div>
            @forelse ($signIns as $s)
                <div class="row-item">
                    <span class="dot tone-secondary"><i class="bi bi-person"></i></span>
                    <span class="grow"><span class="t">{{ $s->user?->full_name ?? 'Deleted user #'.$s->user_id }}</span><span class="s">{{ $s->ip_address ?: 'IP not recorded' }}</span></span>
                    <small class="text-muted text-nowrap">{{ $s->login_time ? \Illuminate\Support\Carbon::parse($s->login_time)->diffForHumans() : '' }}</small>
                </div>
            @empty <div class="empty">Nothing yet.</div> @endforelse
        </div>
    </div>
</div>
@endsection
