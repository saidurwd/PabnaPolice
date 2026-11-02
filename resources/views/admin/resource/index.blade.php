@extends('admin.layouts.app')
@section('title', $def['plural'])
@php($can = fn ($a) => auth()->user()->can($def['slug'].'.'.$a))
@section('content')
<div class="card">
    <div class="card-body">
        <form class="row g-2 mb-3" method="get">
            @if ($def['search'])
                <div class="col-md-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search…"></div>
            @endif
            @foreach ($def['filters'] as $col => [$label, $source])
                <div class="col-md-3"><select class="form-select" name="{{ $col }}" onchange="this.form.submit()">
                    <option value="">{{ $label }}: all</option>
                    @foreach (\App\Admin\Options::get($source) as $k => $v)
                        <option value="{{ $k }}" @selected((string) request($col) === (string) $k)>{{ $v }}</option>
                    @endforeach
                </select></div>
            @endforeach
            <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                @if (request()->query()) <a class="btn btn-link" href="{{ route('admin.resource.index', $def['slug']) }}">Reset</a> @endif</div>
            @if (! empty($def['softDeletes']))
                <div class="col-auto">
                    @if (request()->boolean('trashed'))
                        <a class="btn btn-outline-secondary" href="{{ route('admin.resource.index', $def['slug']) }}"><i class="bi bi-arrow-left"></i> Back to list</a>
                    @else
                        <a class="btn btn-outline-secondary" href="{{ route('admin.resource.index', [$def['slug'], 'trashed' => 1]) }}"><i class="bi bi-trash"></i> Trash</a>
                    @endif
                </div>
            @endif
            @if (in_array('create', $def['actions']) && $can('create'))
                <div class="col text-end"><a class="btn btn-primary" href="{{ route('admin.resource.create', $def['slug']) }}"><i class="bi bi-plus-lg"></i> New {{ strtolower($def['title']) }}</a></div>
            @endif
        </form>

        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr>
                @foreach ($def['columns'] as $col)<th>{{ $col[1] }}</th>@endforeach
                <th class="text-end">Actions</th>
            </tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($def['columns'] as $col)
                        @php([$name, , $type, $arg] = $col + [2 => 'text', 3 => null])
                        @php($val = enum_value($row->{$name}))
                        <td>
                        @switch($type)
                            @case('limit') {{ str_chars_limit(strip_tags((string) $val), $arg) }} @break
                            @case('badge') <span class="badge text-bg-{{ $val === 'Active' ? 'success' : 'secondary' }}">{{ $val }}</span> @break
                            @case('bool') <span class="badge text-bg-{{ $val ? 'success' : 'secondary' }}">{{ $val ? 'Yes' : 'No' }}</span> @break
                            @case('option') {{ \App\Admin\Options::get($arg)[$val] ?? $val }} @break
                            @case('datetime') {{ $val ? \Illuminate\Support\Carbon::parse($val)->format('d M Y H:i') : '—' }} @break
                            @case('image')
                                @php($u = $arg === 'path' ? ($val ? asset($val) : null) : upload_url($arg, $val))
                                @if ($u) <img class="thumb" src="{{ $u }}" loading="lazy" alt=""> @else <span class="text-muted">—</span> @endif @break
                            @default {{ $val }}
                        @endswitch
                        </td>
                    @endforeach
                    <td class="text-end text-nowrap">
                        @if (request()->boolean('trashed') && $can('update'))
                            <form class="d-inline" method="post" action="{{ route('admin.resource.restore', [$def['slug'], $row->id]) }}">@csrf
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button></form>
                        @elseif (in_array('edit', $def['actions']) && $can('update'))
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.resource.edit', [$def['slug'], $row->id]) }}"><i class="bi bi-pencil"></i></a>
                        @endif
                        @if (! request()->boolean('trashed') && in_array('destroy', $def['actions']) && $can('delete'))
                            <form class="d-inline" method="post" action="{{ route('admin.resource.destroy', [$def['slug'], $row->id]) }}" onsubmit="return confirm('{{ ! empty($def['softDeletes']) ? 'Move this record to the trash? You can restore it later.' : 'Delete this record? This cannot be undone.' }}')">
                                @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($def['columns']) + 1 }}" class="text-center text-muted py-4">Nothing found.</td></tr>
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
