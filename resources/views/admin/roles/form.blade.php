@extends('layouts.admin')

@section('title', $role ? 'Edit Role' : 'New Role')

@section('content')
<x-page-header :title="$role ? 'Edit Role: '.$role->name : 'Create Role'"
               subtitle="Select the permissions this role grants. Users inherit all permissions of their role." />

<form method="POST" action="{{ $role ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="max-w-5xl"
      x-data="{ toggleGroup(group, on) { document.querySelectorAll(`[data-group='${group}']`).forEach(el => el.checked = on) } }">
    @csrf
    @if ($role) @method('PUT') @endif

    <div class="card mb-5">
        <div class="card-body">
            <div class="max-w-md">
                <x-form.input label="Role name" name="name" :value="$role?->name" required />
            </div>
        </div>
    </div>

    @php $current = $role?->permissions->pluck('name')->all() ?? []; @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($groups as $group => $permissions)
            <div class="card p-4">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-800 capitalize dark:text-slate-100">{{ str_replace('-', ' ', $group) }}</p>
                    <div class="flex gap-2 text-xs">
                        <button type="button" class="text-primary-700 hover:underline dark:text-primary-300" @click="toggleGroup('{{ $group }}', true)">all</button>
                        <button type="button" class="text-slate-400 hover:underline" @click="toggleGroup('{{ $group }}', false)">none</button>
                    </div>
                </div>
                <div class="space-y-2">
                    @foreach ($permissions as $permission)
                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 dark:text-slate-300">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" data-group="{{ $group }}"
                                   @checked(in_array($permission->name, old('permissions', $current), true))
                                   class="size-4 rounded border-slate-300 text-primary-700 focus:ring-primary-600 dark:border-slate-600 dark:bg-slate-800">
                            {{ explode('.', $permission->name)[1] }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 flex items-center justify-end gap-2">
        <a href="{{ route('admin.roles.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">{{ $role ? 'Save Changes' : 'Create Role' }}</button>
    </div>
</form>
@endsection
