@extends('admin.layouts.app')
@section('title', $role->exists ? 'Role: '.$role->name : 'New role')
@section('content')
<form method="post" action="{{ $role->exists ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}">
    @csrf @if ($role->exists) @method('PUT') @endif
    @if ($locked)
        <div class="alert alert-info">The <strong>{{ $role->name }}</strong> role always has every permission and can't be changed.</div>
    @endif
    <div class="card mb-3"><div class="card-body">
        <label class="form-label" for="name">Role name</label>
        <input id="name" name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="100" required @readonly($locked)>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div></div>

    <div class="card"><div class="card-header bg-white fw-semibold">What this role may do</div><div class="card-body">
        <div class="table-responsive"><table class="table table-sm align-middle mb-3">
            <thead class="table-light"><tr><th>Area</th>@foreach (\App\Support\PermissionCatalog::ABILITIES as $a)<th class="text-center text-capitalize">{{ $a === 'view' ? 'View / list' : $a }}</th>@endforeach<th class="text-center">All</th></tr></thead>
            <tbody>
            @foreach ($modules as $slug => $module)
                <tr data-row>
                    <td>{{ $module['label'] }}</td>
                    @foreach (\App\Support\PermissionCatalog::ABILITIES as $a)
                        <td class="text-center">
                            @if (in_array($a, $module['abilities']))
                                <input class="form-check-input perm" type="checkbox" name="permissions[]" value="{{ $slug }}.{{ $a }}"
                                    @checked($locked || isset($granted[$slug.'.'.$a]) || in_array($slug.'.'.$a, old('permissions', []))) @disabled($locked)>
                            @endif
                        </td>
                    @endforeach
                    <td class="text-center"><input class="form-check-input row-all" type="checkbox" @disabled($locked) title="Select the whole row"></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @unless ($locked)<button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save role</button>@endunless
        <a class="btn btn-outline-secondary" href="{{ route('admin.roles.index') }}">{{ $locked ? 'Back' : 'Cancel' }}</a>
    </div></div>
</form>
@push('scripts')
<script>
document.querySelectorAll('[data-row]').forEach(function (row) {
    var boxes = row.querySelectorAll('.perm'), all = row.querySelector('.row-all');
    var sync = function () { all.checked = boxes.length > 0 && Array.prototype.every.call(boxes, function (b) { return b.checked; }); };
    all.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = all.checked; }); });
    boxes.forEach(function (b) { b.addEventListener('change', sync); });
    sync();
});
</script>
@endpush
@endsection
