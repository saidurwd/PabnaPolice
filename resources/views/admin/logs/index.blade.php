@extends('admin.layouts.app')
@section('title', $title)
@php
    $isSecurity = $kind === 'security';
    $sev = ['info' => 'info', 'warning' => 'warning', 'critical' => 'danger'];
    $evt = ['created' => 'success', 'updated' => 'primary', 'trashed' => 'warning', 'deleted' => 'danger', 'restored' => 'info'];
    $intro = [
        'audit' => 'Every change made to the website\'s records (content, categories, banners, documents, links, users, settings) with the old and new values.',
        'activity' => 'What signed-in users did: sign-ins and sign-outs, settings saved, passwords changed, roles edited, files uploaded.',
        'security' => 'Failed sign-ins, lock-outs, password and two-factor changes, role changes and refused access. Review warnings and critical events.',
    ][$kind];
@endphp
@section('content')
<div class="card">
    <div class="card-body">
        <p class="text-muted small mb-3">{{ $intro }}</p>
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search…"></div>
            @if ($isSecurity)
                <div class="col-md-2"><select class="form-select" name="severity"><option value="">Severity: all</option>
                    @foreach ($severities as $k => $v)<option value="{{ $k }}" @selected(request('severity') === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-3"><select class="form-select" name="type"><option value="">Type: all</option>
                    @foreach ($types as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach</select></div>
            @else
                <div class="col-md-2"><select class="form-select" name="event"><option value="">Event: all</option>
                    @foreach ($events as $k => $v)<option value="{{ $k }}" @selected(request('event') === (string) $k)>{{ $v }}</option>@endforeach</select></div>
                @if ($kind === 'audit')
                    <div class="col-md-2"><select class="form-select" name="subject_type"><option value="">Record type: all</option>
                        @foreach ($subjects as $k => $v)<option value="{{ $k }}" @selected(request('subject_type') === $k)>{{ $v }}</option>@endforeach</select></div>
                @endif
            @endif
            <div class="col-md-2"><select class="form-select" name="user_id"><option value="">User: all</option>
                @foreach ($users as $k => $v)<option value="{{ $k }}" @selected((string) request('user_id') === (string) $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-2"><input class="form-control" name="ip" value="{{ request('ip') }}" placeholder="IP address"></div>
            <div class="col-md-2"><input class="form-control" type="date" name="from" value="{{ request('from') }}" title="From"></div>
            <div class="col-md-2"><input class="form-control" type="date" name="to" value="{{ request('to') }}" title="To"></div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                @if (request()->query()) <a class="btn btn-link" href="{{ url()->current() }}">Reset</a> @endif
            </div>
            <div class="col text-end"><a class="btn btn-outline-primary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download"></i> Export CSV</a></div>
        </form>

        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                <th>Time</th>
                @if ($isSecurity) <th>Severity</th><th>Event</th> @else <th>Event</th> @endif
                <th>{{ $isSecurity ? 'Details' : 'Description' }}</th><th>User</th><th>IP address</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $r)
                @php($id = 'r'.$r->id)
                @php($data = $isSecurity ? $r->details : $r->properties)
                <tr>
                    <td class="text-nowrap" title="{{ $r->created_at }}">{{ $r->created_at->format('d M Y H:i:s') }}</td>
                    @if ($isSecurity)
                        <td><span class="badge text-bg-{{ $sev[$r->severity] ?? 'secondary' }}">{{ ucfirst($r->severity) }}</span></td>
                        <td class="text-nowrap">{{ \App\Support\Logs\Security::TYPES[$r->type] ?? $r->type }}</td>
                        <td>{{ $r->message }}@if ($r->identifier) <span class="text-muted">· {{ $r->identifier }}</span>@endif</td>
                        <td>{{ $users[$r->user_id] ?? ($r->user_id ? '#'.$r->user_id : '—') }}</td>
                    @else
                        <td><span class="badge text-bg-{{ $evt[$r->event] ?? 'secondary' }}">{{ $r->event }}</span></td>
                        <td>{{ $r->description }}</td>
                        <td>{{ $r->user_name ?: '—' }}</td>
                    @endif
                    <td class="text-nowrap">{{ $r->ip_address ?: '—' }}</td>
                    <td class="text-end"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#{{ $id }}"><i class="bi bi-chevron-down"></i></button></td>
                </tr>
                <tr class="collapse" id="{{ $id }}"><td colspan="{{ $isSecurity ? 7 : 6 }}" class="bg-body-tertiary small">
                    <div class="text-muted mb-2"><i class="bi bi-window"></i> {{ \App\Support\Logs\UserAgent::label($r->user_agent) }}</div>
                    @if ($kind === 'audit' && ! empty($data))
                        <table class="table table-sm table-bordered bg-white mb-0">
                            <thead><tr><th style="width:20%">Field</th><th>Before</th><th>After</th></tr></thead>
                            <tbody>
                            @foreach (array_unique(array_merge(array_keys($data['old'] ?? []), array_keys($data['new'] ?? []))) as $field)
                                <tr><td><code>{{ $field }}</code></td>
                                    <td class="text-danger">{{ $data['old'][$field] ?? '—' }}</td>
                                    <td class="text-success">{{ $data['new'][$field] ?? '—' }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    @elseif (! empty($data))
                        <table class="table table-sm table-bordered bg-white mb-0"><tbody>
                        @foreach ($data as $k => $v)
                            <tr><td style="width:20%"><code>{{ $k }}</code></td><td>{{ is_scalar($v) || $v === null ? $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}</td></tr>
                        @endforeach
                        </tbody></table>
                    @else
                        <span class="text-muted">No extra details.</span>
                    @endif
                </td></tr>
            @empty
                <tr><td colspan="{{ $isSecurity ? 7 : 6 }}" class="text-center text-muted py-4">Nothing found.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">{{ number_format($rows->total()) }} record(s)</small>
            {{ $rows->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
