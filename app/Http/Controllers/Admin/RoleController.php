<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()->withCount(['permissions', 'users'])->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorize('roles.manage');

        return view('admin.roles.form', ['role' => null, 'groups' => $this->permissionGroups()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('roles.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        AuditLog::record('role_created', null, null, $data, "Role {$data['name']} created");

        return redirect()->route('admin.roles.index')->with('success', "Role {$role->name} created.");
    }

    public function edit(Role $role): View
    {
        $this->authorize('roles.manage');

        return view('admin.roles.form', ['role' => $role, 'groups' => $this->permissionGroups()]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('roles.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:roles,name,'.$role->id],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        if ($role->name === 'Super Administrator') {
            return back()->with('error', 'The Super Administrator role cannot be modified.');
        }

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        AuditLog::record('role_updated', null, null, $data, "Role {$role->name} updated");

        return redirect()->route('admin.roles.index')->with('success', "Role {$role->name} updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('roles.manage');

        if (in_array($role->name, ['Super Administrator', 'Listener'], true)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Reassign the users on this role before deleting it.');
        }

        $role->delete();
        AuditLog::record('role_deleted', null, null, null, "Role {$role->name} deleted");

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }

    /** @return array<string, \Illuminate\Support\Collection> */
    private function permissionGroups(): array
    {
        return Permission::query()->orderBy('name')->get()
            ->groupBy(fn (Permission $permission) => explode('.', $permission->name)[0])
            ->sortKeys()
            ->all();
    }
}
