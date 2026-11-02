<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/** Roles and what each may do. Replaces the old "user groups" + permission matrix. */
class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get(),
            'totalPermissions' => count(PermissionCatalog::names()),
            'superRole' => config('admin.super_role'),
        ]);
    }

    public function create()
    {
        return view('admin.roles.form', ['role' => new Role, 'granted' => [], 'modules' => PermissionCatalog::modules(), 'locked' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')],
            'permissions' => 'array',
            'permissions.*' => Rule::in(PermissionCatalog::names()),
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role)
    {
        return view('admin.roles.form', [
            'role' => $role,
            'granted' => $role->permissions->pluck('name')->flip()->all(),
            'modules' => PermissionCatalog::modules(),
            'locked' => $role->name === config('admin.super_role'),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === config('admin.super_role'), 403, 'The Super Users role always has every permission.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'array',
            'permissions.*' => Rule::in(PermissionCatalog::names()),
        ]);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', "Role \"{$role->name}\" saved.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === config('admin.super_role')) {
            return back()->with('error', 'The Super Users role cannot be deleted.');
        }
        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete: users still have this role. Give them another role first.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }
}
