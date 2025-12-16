<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Role::class);

        $roles = Role::where('name','!=','master')->withCount('users')->withCount('permissions')->paginate(10);

        return view('roles.index',compact('roles'));
    }


    public function create()
    {
        Gate::authorize('create', Role::class);

        $permissionsConfig = config('permissions');
        $groupedPermissions = [];

        foreach ($permissionsConfig as $model => $actions) {
            foreach ($actions as $action) {
                $permissionName = strtolower($action) . ' ' . strtolower($model);
                $permission = Permission::where('name', $permissionName)->first();

                if ($permission) {
                    $groupedPermissions[$model][$action] = $permission;
                }
            }
        }

        return view('roles.create')->with('permissions',$groupedPermissions);

    }

    public function store(Request $request)
    {
        Gate::authorize('create', Role::class);

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);


        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success','Role created succesfully');

    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        Gate::authorize('update', $role);

        $permissionsConfig = config('permissions');
        $groupedPermissions = [];

        foreach ($permissionsConfig as $model => $actions) {
            foreach ($actions as $action) {
                $permissionName = strtolower($action) . ' ' . strtolower($model);
                $permission = Permission::where('name', $permissionName)->first();

                if ($permission) {
                    $groupedPermissions[$model][$action] = $permission;
                }
            }
        }


        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('groupedPermissions', 'role', 'rolePermissions'));
    }


    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        Gate::authorize('update', $role);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role->name = $validated['name'];
        $role->save();

        $permissions = $validated['permissions'] ?? [];
        $role->syncPermissions($permissions);

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        Gate::authorize('delete', $role);

        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }

}
