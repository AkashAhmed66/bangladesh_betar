<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('email', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('type'), fn ($q) => $q->where('user_type', $request->string('type')))
            ->when($request->filled('role'), fn ($q) => $q->role($request->string('role')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('users.create');

        return view('admin.users.form', ['user' => null, 'roles' => Role::query()->orderBy('name')->pluck('name')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('users.create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'user_type' => ['required', Rule::in(['staff', 'listener'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user = User::query()->create($data + ['email_verified_at' => now()]);
        $user->syncRoles([$data['role']]);

        AuditLog::record('user_created', $user, null, ['role' => $data['role']]);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} created.");
    }

    public function edit(User $user): View
    {
        $this->authorize('users.edit');

        return view('admin.users.form', ['user' => $user, 'roles' => Role::query()->orderBy('name')->pluck('name')]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.edit');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'user_type' => ['required', Rule::in(['staff', 'listener'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
            'role' => ['required', 'exists:roles,name'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.delete');

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        AuditLog::record('user_deleted', $user);

        return redirect()->route('admin.users.index')->with('success', 'User deactivated and removed.');
    }
}
