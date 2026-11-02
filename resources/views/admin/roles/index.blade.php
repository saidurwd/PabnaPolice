@extends('admin.layouts.app')
@section('title', 'Roles & permissions')
@section('content')
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">A role is a set of permissions. Give each person one role under <a href="{{ route('admin.resource.index', 'user') }}">Users</a>.</p>
        @can('role.create')<a class="btn btn-primary" href="{{ route('admin.roles.create') }}"><i class="bi bi-plus-lg"></i> New role</a>@endcan
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Role</th><th>People</th><th>Permissions</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @foreach ($roles as $role)
            <tr>
                <td class="fw-semibold">{{ $role->name }} @if ($role->name === $superRole)<span class="badge text-bg-warning ms-1">everything</span>@endif</td>
                <td>{{ $role->users_count }}</td>
                <td>@if ($role->name === $superRole) all @else {{ $role->permissions_count }} of {{ $totalPermissions }} @endif</td>
                <td class="text-end text-nowrap">
                    @can('role.update')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.roles.edit', $role->id) }}"><i class="bi bi-pencil"></i> {{ $role->name === $superRole ? 'View' : 'Edit' }}</a>@endcan
                    @if ($role->name !== $superRole)
                        @can('role.delete')
                            <form class="d-inline" method="post" action="{{ route('admin.roles.destroy', $role->id) }}" onsubmit="return confirm('Delete this role?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        @endcan
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div></div>
@endsection
