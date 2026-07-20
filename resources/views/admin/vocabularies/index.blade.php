@extends('layouts.admin')

@section('title', $label)

@section('content')
<x-page-header title="Controlled Vocabularies" subtitle="Administrator-defined metadata values without code changes (FR-MET-03)" />

{{-- Type tabs --}}
<div class="mb-5 flex flex-wrap gap-1 rounded-(--radius-app) border border-slate-200 bg-white p-1 dark:border-slate-800 dark:bg-slate-900 w-fit">
    @foreach ($types as $t)
        <a href="{{ route('admin.vocabularies.index', $t) }}"
           class="rounded-lg px-3.5 py-1.5 text-sm font-medium capitalize transition {{ $t === $type ? 'bg-primary-700 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
            {{ $t }}
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="table-shell">
            <table class="table-app">
                <thead><tr><th>Name</th><th>বাংলা</th>@if ($isLanguage)<th>Code</th>@endif @if ($hasType)<th>Scope</th>@endif<th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr x-data="{ editing: false }">
                            <td>
                                <template x-if="!editing"><span class="font-medium">{{ $item->name }}</span></template>
                                <template x-if="editing">
                                    <form method="POST" action="{{ route('admin.vocabularies.update', [$type, $item->id]) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf @method('PUT')
                                        <input name="name" value="{{ $item->name }}" class="form-input w-40" required>
                                        <input name="name_bn" value="{{ $item->name_bn }}" class="form-input w-40" placeholder="বাংলা">
                                        @if ($isLanguage)<input name="code" value="{{ $item->code }}" class="form-input w-20">@endif
                                        @if ($hasType)
                                            <select name="type" class="form-input w-28">
                                                @foreach (['content', 'story', 'ad'] as $scope)
                                                    <option value="{{ $scope }}" @selected($item->type === $scope)>{{ ucfirst($scope) }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                        <button class="btn-primary btn-sm">Save</button>
                                        <button type="button" class="btn-secondary btn-sm" @click="editing = false">Cancel</button>
                                    </form>
                                </template>
                            </td>
                            <td x-show="!editing" class="text-sm">{{ $item->name_bn ?? '—' }}</td>
                            @if ($isLanguage)<td x-show="!editing" class="text-sm">{{ $item->code }}</td>@endif
                            @if ($hasType)<td x-show="!editing"><span class="badge-slate">{{ ucfirst($item->type) }}</span></td>@endif
                            <td x-show="!editing">
                                <div class="flex items-center justify-end gap-1">
                                    @can('taxonomies.manage')
                                        <button class="btn-ghost btn-sm" @click="editing = true"><x-icon name="pencil" class="size-4" /></button>
                                        <x-confirm-delete :action="route('admin.vocabularies.destroy', [$type, $item->id])" confirm="Remove {{ $item->name }}?" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No entries yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('taxonomies.manage')
        <div class="card h-fit">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Add {{ Str::singular($label) }}</h3></div>
            <form method="POST" action="{{ route('admin.vocabularies.store', $type) }}" class="card-body space-y-4">
                @csrf
                <x-form.input label="Name" name="name" required />
                <x-form.input label="Name (বাংলা)" name="name_bn" />
                @if ($isLanguage)<x-form.input label="Code" name="code" help="ISO-style code, e.g. bn, en, bn-syl" />@endif
                @if ($hasType)
                    <x-form.select label="Scope" name="type" value="content"
                                   :options="['content' => 'Content', 'story' => 'Story', 'ad' => 'Advertisement']" />
                @endif
                <button class="btn-primary w-full">Add</button>
            </form>
        </div>
    @endcan
</div>
@endsection
