@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('content')
<x-page-header title="Roles & Permissions" subtitle="RBAC with granular permissions — every portal action is gated (FR-USR-03)">
    @can('roles.manage')
        <a href="{{ route('admin.roles.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Role</a>
    @endcan
</x-page-header>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($roles as $role)
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">
                        <x-icon name="shield" class="size-5" />
                    </span>
                    <div>
                        <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $role->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}</p>
                    </div>
                </div>
                @can('roles.manage')
                    @if ($role->name !== 'Super Administrator')
                        <div class="flex gap-1">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                            <x-confirm-delete :action="route('admin.roles.destroy', $role)" confirm="Delete role {{ $role->name }}?" />
                        </div>
                    @endif
                @endcan
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                <span class="text-sm text-slate-500 dark:text-slate-400">Permissions</span>
                <span class="badge-primary">{{ $role->name === 'Super Administrator' ? 'All' : $role->permissions_count }}</span>
            </div>
        </div>
    @endforeach
</div>
@endsection
