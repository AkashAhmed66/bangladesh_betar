@extends('layouts.admin')

@section('title', $user ? 'Edit User' : 'New User')

@section('content')
<x-page-header :title="$user ? 'Edit User: '.$user->name : 'Create User'"
               subtitle="Accounts are audited; roles control every permission in the portal." />

<form method="POST" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}" class="max-w-3xl"
      x-data="{ userType: '{{ old('user_type', $user?->user_type ?? 'staff') }}', role: '{{ old('role', $user?->getRoleNames()->first()) }}' }">
    @csrf
    @if ($user) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Full name" name="name" :value="$user?->name" required />
            <x-form.input label="E-mail" name="email" type="email" :value="$user?->email" required />
            <x-form.input label="Phone" name="phone" :value="$user?->phone" />
            <x-form.input label="Password" name="password" type="password"
                          :help="$user ? 'Leave blank to keep the current password.' : 'Minimum 6 characters.'" :required="! $user" />
            <x-form.select label="Account type" name="user_type" x-model="userType" :value="$user?->user_type ?? 'staff'" required
                           :options="['staff' => 'Staff (Admin Portal)', 'listener' => 'Listener (Public App)', 'both' => 'Both (Admin + Public App)']" />
            <x-form.select label="Status" name="status" :value="$user?->status ?? 'active'" required
                           :options="['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned']" />

            {{-- Staff choose an admin role; listeners get the fixed Listener role automatically. --}}
            <div x-show="userType === 'staff'" x-cloak class="sm:col-span-2">
                <x-form.select label="Role" name="role" x-model="role" x-bind:disabled="userType !== 'staff'"
                               :value="$user?->getRoleNames()->first()" placeholder="Select role…"
                               :options="$roles->reject(fn ($r) => in_array($r, ['Listener', 'Artist']))->mapWithKeys(fn ($r) => [$r => $r])->all()"
                               help="Determines every permission this staff member has." />
            </div>

            {{-- Dual-app account: choose the admin role. Picking "Artist" also creates a linked public artist profile. --}}
            <div x-show="userType === 'both'" x-cloak class="sm:col-span-2">
                <x-form.select label="Role" name="role" x-model="role" x-bind:disabled="userType !== 'both'"
                               :value="$user?->getRoleNames()->first()" placeholder="Select role…"
                               :options="$roles->reject(fn ($r) => $r === 'Listener')->mapWithKeys(fn ($r) => [$r => $r])->all()"
                               help="Signs in to both the Admin Portal and the public app with the same credentials. Choose “Artist” to create a public artist profile." />
            </div>

            {{-- Artist role: an Artist profile is created and kept in sync automatically. --}}
            <div x-show="userType === 'both' && role === 'Artist'" x-cloak class="grid grid-cols-1 gap-5 sm:col-span-2 sm:grid-cols-2">
                <x-form.select label="Artist type" name="artist_type"
                               :value="$user?->artist?->artist_type ?? 'singer'"
                               :options="collect(\App\Models\Artist::TYPES)->mapWithKeys(fn ($t) => [$t => ucfirst(str_replace('_', ' ', $t))])->all()"
                               help="Creates a linked profile in the Artists module." />
                <div class="flex flex-col justify-center gap-2 rounded-(--radius-app) border border-slate-200 px-4 py-3 dark:border-slate-800">
                    <x-form.toggle label="Verified artist" name="is_verified" :checked="(bool) $user?->artist?->is_verified"
                                   help="Shows a verified badge on the public profile. Admin-controlled." />
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 sm:col-span-2">
                    The artist can sign in here to edit their profile (photo, cover banner, bio, links) and use the public app with the same credentials.
                </p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $user ? 'Save Changes' : 'Create User' }}</button>
        </div>
    </div>
</form>
@endsection
