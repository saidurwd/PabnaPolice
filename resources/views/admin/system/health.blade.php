@extends('admin.layouts.app')
@section('title', 'System health')
@php
    $tone = ['ok' => 'success', 'warn' => 'warning', 'fail' => 'danger'];
    $banner = ['ok' => ['success', 'bi-check-circle-fill', 'Everything is working'], 'warn' => ['warning', 'bi-exclamation-triangle-fill', 'Working, but something needs attention'], 'fail' => ['danger', 'bi-x-octagon-fill', 'Something is broken']][$overall];
    $label = fn ($k) => str_replace('Php', 'PHP', ucfirst(str_replace('_', ' ', $k)));
@endphp
@section('content')
<div class="alert alert-{{ $banner[0] }} d-flex align-items-center justify-content-between" role="alert">
    <div><i class="bi {{ $banner[1] }} me-2"></i><strong>{{ $banner[2] }}</strong>
        <span class="small ms-2">Checked {{ $checkedAt->format('d M Y H:i:s') }}</span></div>
    <a class="btn btn-sm btn-outline-{{ $banner[0] }}" href="{{ url()->current() }}"><i class="bi bi-arrow-repeat"></i> Check again</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Disk space</div>
        @if ($disk)
            <div class="fs-5">{{ \App\Support\SystemInfo::bytes($disk['free']) }} free</div>
            <div class="progress mt-2" style="height:8px"><div class="progress-bar bg-{{ $disk['used_percent'] > 90 ? 'danger' : ($disk['used_percent'] > 75 ? 'warning' : 'success') }}" style="width:{{ $disk['used_percent'] }}%"></div></div>
            <div class="small text-muted mt-1">{{ $disk['used_percent'] }}% of {{ \App\Support\SystemInfo::bytes($disk['total']) }} used</div>
        @else <div class="fs-5">Unknown</div> @endif
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Database</div>
        <div class="fs-5">{{ $database['size_mb'] }} MB</div>
        <div class="small text-muted">{{ $database['tables'] }} tables · answered in {{ $database['latency_ms'] }} ms</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Application errors (24 h)</div>
        <div class="fs-5 {{ $logErrors ? 'text-danger' : '' }}">{{ $logErrors === null ? 'Unknown' : $logErrors }}</div>
        <div class="small text-muted">from storage/logs/laravel.log</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Security (24 h)</div>
        <div class="fs-5">{{ $security['failed'] }} failed sign-in(s)</div>
        <div class="small {{ $security['critical'] ? 'text-danger' : 'text-muted' }}">{{ $security['critical'] }} critical event(s)
            @can('security-events.view') · <a href="{{ route('admin.security-events') }}">view</a>@endcan</div>
    </div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-header fw-semibold">Self-checks</div>
        <table class="table mb-0 align-middle">
            <tbody>
            @foreach ($checks as $name => $c)
                <tr>
                    <td style="width:1%" class="text-nowrap"><span class="badge text-bg-{{ $tone[$c['status']] }}">{{ strtoupper($c['status']) }}</span></td>
                    <td class="text-nowrap fw-semibold">{{ $label($name) }}</td>
                    <td class="text-muted small">{{ $c['message'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-header fw-semibold">Server</div>
        <table class="table table-sm mb-0">
            @foreach ($server as $k => $v)<tr><td class="text-muted" style="width:42%">{{ $k }}</td><td>{{ $v }}</td></tr>@endforeach
        </table></div>
        <div class="card"><div class="card-header fw-semibold">Storage used</div>
        <table class="table table-sm mb-0">
            @foreach ($folders as $k => $v)<tr><td class="text-muted" style="width:42%">{{ $k }}</td><td>{{ \App\Support\SystemInfo::bytes($v) }}</td></tr>@endforeach
        </table></div>
    </div>
</div>
@endsection
