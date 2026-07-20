@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<x-page-header title="User Management" subtitle="Staff and listener accounts with role-based access (M01)">
    @can('users.create')
        <a href="{{ route('admin.users.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New User</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or e-mail…" class="form-input w-56 pl-9">
            </div>
            <select name="type" class="form-input w-36" onchange="this.form.submit()">
                <option value="">All types</option>
                <option value="staff" @selected(request('type') === 'staff')>Staff</option>
                <option value="listener" @selected(request('type') === 'listener')>Listener</option>
            </select>
            <select name="role" class="form-input w-48" onchange="this.form.submit()">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $users->total() }} accounts</span>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-800 dark:bg-primary-500/20 dark:text-primary-300">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-slate-800 dark:text-slate-100">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge-{{ $user->user_type === 'staff' ? 'primary' : 'blue' }}">{{ ucfirst($user->user_type) }}</span></td>
                        <td class="text-sm">{{ $user->getRoleNames()->implode(', ') ?: '—' }}</td>
                        <td><x-status-badge :status="$user->status" /></td>
                        <td class="text-sm text-slate-500">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('users.edit')
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn-ghost btn-sm" title="Edit"><x-icon name="pencil" class="size-4" /></a>
                                @endcan
                                @can('users.delete')
                                    <x-confirm-delete :action="route('admin.users.destroy', $user)" confirm="Delete user {{ $user->name }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="users" title="No users found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $users->links() }}</div>
    @endif
</div>
@endsection
