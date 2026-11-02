@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="row g-3 mb-4">
    @foreach ($stats as [$label, $count, $icon, $acl])
        <div class="col-6 col-lg"><div class="card"><div class="card-body stat"><i class="bi {{ $icon }}"></i><div><div class="n">{{ number_format($count) }}</div><small class="text-muted">{{ $label }}</small></div></div></div></div>
    @endforeach
</div>
<div class="row g-3">
    <div class="col-lg-7"><div class="card"><div class="card-header bg-white fw-semibold">Latest content</div>
        <div class="list-group list-group-flush">
            @forelse ($recent as $c)
                <a class="list-group-item list-group-item-action d-flex justify-content-between gap-3" href="{{ route('admin.resource.edit', ['content', $c->id]) }}">
                    <span>{{ str_chars_limit($c->title, 80) }}</span><small class="text-muted text-nowrap">{{ legacy_date($c->published_at) }}</small></a>
            @empty <div class="list-group-item text-muted">No content yet.</div> @endforelse
        </div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-header bg-white fw-semibold">Recent admin activity</div>
        <ul class="list-group list-group-flush">
            @forelse ($visits as $v)
                <li class="list-group-item d-flex justify-content-between"><span>{{ $v->user_name }} <small class="text-muted">{{ str_chars_limit($v->page_link, 30) }}</small></span><small class="text-muted">{{ $v->server_time }}</small></li>
            @empty <li class="list-group-item text-muted">Nothing yet.</li> @endforelse
        </ul></div></div>
</div>
@endsection
