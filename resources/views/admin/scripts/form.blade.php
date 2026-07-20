@extends('layouts.admin')

@section('title', $script ? 'Edit Script' : 'New Script')

@section('content')
<x-page-header :title="$script ? 'Edit: '.$script->title : 'New Script'"
               subtitle="Scripts are versioned — link a parent to track revisions (FR-MKT-04)" />

<form method="POST" action="{{ $script ? route('admin.scripts.update', $script) : route('admin.scripts.store') }}" class="max-w-3xl">
    @csrf
    @if ($script) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-form.input label="Title" name="title" :value="$script?->title" required /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Script body" name="body" :value="$script?->body" rows="12" required help="The full spoken/written script content." /></div>
            <x-form.input label="Version number" name="version_number" type="number" :value="$script?->version_number ?? 1" required help="Increment when creating a revision." />
            <x-form.select label="Status" name="status" :value="$script?->status ?? 'draft'" required
                           :options="collect($statuses)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()" />
            <div class="sm:col-span-2">
                <x-form.select label="Parent script" name="parent_script_id" :value="$script?->parent_script_id" placeholder="— none (original) —"
                               :options="$parents->all()" help="Link the script this version was derived from." />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.scripts.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $script ? 'Save Changes' : 'Create Script' }}</button>
        </div>
    </div>
</form>
@endsection
