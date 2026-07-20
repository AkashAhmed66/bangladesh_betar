@extends('layouts.admin')

@section('title', 'Rights Holders')

@section('content')
<x-page-header title="Rights Holders" subtitle="Persons and organizations that own content rights (M14)">
    @can('rights.manage')
        <a href="{{ route('admin.rights-holders.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> New Holder</a>
    @endcan
</x-page-header>

<div class="card">
    <div class="card-header">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, contact or email…" class="form-input w-64">
            <select name="holder_type" class="form-input w-40" onchange="this.form.submit()">
                <option value="">All types</option>
                <option value="person" @selected(request('holder_type') === 'person')>Person</option>
                <option value="organization" @selected(request('holder_type') === 'organization')>Organization</option>
            </select>
            <button class="btn-secondary btn-sm">Filter</button>
        </form>
    </div>

    <div class="table-shell">
        <table class="table-app">
            <thead><tr><th>Holder</th><th>Type</th><th>Contact</th><th>Records</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($holders as $holder)
                    <tr>
                        <td>
                            <p class="font-medium text-slate-800 dark:text-slate-100">{{ $holder->name }}</p>
                            @if ($holder->address)<p class="text-xs text-slate-500 dark:text-slate-400">{{ $holder->address }}</p>@endif
                        </td>
                        <td>
                            <span class="badge-{{ $holder->holder_type === 'organization' ? 'blue' : 'slate' }}">{{ ucfirst($holder->holder_type) }}</span>
                        </td>
                        <td class="text-sm text-slate-600 dark:text-slate-300">
                            @if ($holder->contact_person)<p class="font-medium">{{ $holder->contact_person }}</p>@endif
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $holder->email ?? '—' }}@if ($holder->phone) · {{ $holder->phone }}@endif
                            </p>
                        </td>
                        <td><span class="badge-slate">{{ $holder->rights_records_count }}</span></td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('rights.manage')
                                    <a href="{{ route('admin.rights-holders.edit', $holder) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" /></a>
                                    <x-confirm-delete :action="route('admin.rights-holders.destroy', $holder)" confirm="Delete rights holder {{ $holder->name }}?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="scale" title="No rights holders yet" message="Add the persons and organizations that own rights over archive content." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($holders->hasPages())
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $holders->links() }}</div>
    @endif
</div>
@endsection
