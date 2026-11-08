@extends('admin.layouts.app')
@section('title', 'About')
@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-shield-check display-5 text-primary me-3"></i>
                <div><h2 class="h4 mb-0">{{ setting('site_name') }}</h2>
                    <div class="text-muted">Website &amp; administration panel</div></div>
            </div>
            <table class="table table-sm mb-0">
                <tr><td class="text-muted" style="width:40%">Version</td><td>{{ $release['version'] }}@if ($release['commit']) <span class="text-muted">· build {{ $release['commit'] }}</span>@endif</td></tr>
                <tr><td class="text-muted">Public website</td><td><a href="{{ url('/') }}" target="_blank">{{ url('/') }}</a></td></tr>
                <tr><td class="text-muted">Administrator e-mail</td><td>{{ setting('admin_email') }}</td></tr>
                <tr><td class="text-muted">Developed by</td><td>{{ setting('footer_credit') }}</td></tr>
                <tr><td class="text-muted">Signed in as</td><td>{{ auth()->user()->full_name }} ({{ auth()->user()->roles->pluck('name')->join(', ') }})</td></tr>
            </table>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-header fw-semibold">Built with</div>
        <table class="table table-sm mb-0">
            @foreach ($packages as $k => $v)<tr><td class="text-muted" style="width:40%">{{ $k }}</td><td>{{ $v }}</td></tr>@endforeach
        </table></div>
    </div>
    <div class="col-12">
        <div class="card"><div class="card-header fw-semibold">Environment</div>
        <table class="table table-sm mb-0">
            @foreach ($server as $k => $v)<tr><td class="text-muted" style="width:25%">{{ $k }}</td><td>{{ $v }}</td></tr>@endforeach
        </table></div>
    </div>
</div>
@endsection
