@extends('layouts.admin')

@section('title', $section ? 'Edit Home Section' : 'New Home Section')

@section('content')
<x-page-header :title="$section ? 'Edit: '.$section->title : 'New Home Section'"
               subtitle="Configure a home screen row and its scheduling window (FR-CUR-01/03)" />

<form method="POST" action="{{ $section ? route('admin.home-sections.update', $section) : route('admin.home-sections.store') }}" class="max-w-3xl">
    @csrf
    @if ($section) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Title" name="title" :value="$section?->title" required help="Slug is generated automatically from the title." />
            <x-form.input label="Title (Bangla)" name="title_bn" :value="$section?->title_bn" help="Optional secondary heading (৳ / বাংলা)." />

            <x-form.select label="Section type" name="section_type" :value="$section?->section_type ?? 'custom'" required
                           :options="$sectionTypes" help="Dynamic types resolve at request time; 'custom' uses curated items." />
            <x-form.select label="Layout" name="layout" :value="$section?->layout ?? 'row'" required :options="$layouts" />

            <x-form.input label="Position" name="position" type="number" :value="$section?->position ?? 0" required help="Lower numbers appear first." />
            <x-form.input label="Max items" name="max_items" type="number" :value="$section?->max_items ?? 12" required help="Cap on cards shown in this section." />

            <x-form.input label="Starts at" name="starts_at" type="datetime-local" :value="$section?->starts_at?->format('Y-m-d\TH:i')" help="Leave blank to start immediately." />
            <x-form.input label="Ends at" name="ends_at" type="datetime-local" :value="$section?->ends_at?->format('Y-m-d\TH:i')" help="Leave blank for no end date." />

            <div class="sm:col-span-2">
                <x-form.toggle label="Active" name="is_active" :checked="$section?->is_active ?? true" help="Only active sections within their schedule window appear on the public home screen." />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.home-sections.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $section ? 'Save Changes' : 'Create Section' }}</button>
        </div>
    </div>
</form>
@endsection
