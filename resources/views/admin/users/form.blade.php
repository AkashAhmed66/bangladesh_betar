@extends('layouts.admin')

@section('title', $user ? 'Edit User' : 'New User')

@section('content')
<x-page-header :title="$user ? 'Edit User: '.$user->name : 'Create User'"
               subtitle="Accounts are audited; roles control every permission in the portal." />

<form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" class="max-w-3xl">
    @csrf
    @if ($user) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Full name" name="name" :value="$user?->name" required />
            <x-form.input label="E-mail" name="email" type="email" :value="$user?->email" required />
            <x-form.input label="Phone" name="phone" :value="$user?->phone" />
            <x-form.input label="Password" name="password" type="password"
                          :help="$user ? 'Leave blank to keep the current password.' : 'Minimum 6 characters.'" :required="! $user" />
            <x-form.select label="Account type" name="user_type" :value="$user?->user_type ?? 'staff'" required
                           :options="['staff' => 'Staff (Admin Portal)', 'listener' => 'Listener (Public App)']" />
            <x-form.select label="Status" name="status" :value="$user?->status ?? 'active'" required
                           :options="['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned']" />
            <x-form.select label="Role" name="role" :value="$user?->getRoleNames()->first()" required placeholder="Select role…"
                           :options="$roles->mapWithKeys(fn ($r) => [$r => $r])->all()"
                           help="Determines every permission this user has." />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $user ? 'Save Changes' : 'Create User' }}</button>
        </div>
    </div>
</form>
@endsection
