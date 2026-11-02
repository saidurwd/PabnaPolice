@extends('admin.layouts.app')
@section('title', 'Permissions: '.$group->title)
@section('content')
@if ($group->id === 1)
    <div class="alert alert-info">Super Users always have full access to everything; this cannot be changed.</div>
@endif
<form method="post" action="{{ route('admin.access.update', $group->id) }}">@csrf @method('PUT')
<div class="card"><div class="card-body"><div class="table-responsive">
<table class="table table-sm align-middle">
    <thead class="table-light"><tr><th>Module</th><th>Allowed actions</th></tr></thead>
    <tbody>
    @foreach ($modules as $m)
        <tr><td class="fw-semibold">{{ $m->title }}</td><td>
            @forelse ($actions[$m->id] ?? [] as $a)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="p{{ $m->id }}{{ $a->action }}" name="perm[{{ $m->id }}][{{ $a->action }}]" value="1"
                        @checked($group->id === 1 || isset($granted[strtolower($m->controller).'|'.strtolower($a->action)])) @disabled($group->id === 1)>
                    <label class="form-check-label" for="p{{ $m->id }}{{ $a->action }}">{{ $a->title }}</label>
                </div>
            @empty <span class="text-muted">No actions defined.</span> @endforelse
        </td></tr>
    @endforeach
    </tbody>
</table></div>
@if ($group->id !== 1)<button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save permissions</button>@endif
<a class="btn btn-outline-secondary" href="{{ route('admin.resource.index', 'user-group') }}">Back</a>
</div></div>
</form>
@endsection
