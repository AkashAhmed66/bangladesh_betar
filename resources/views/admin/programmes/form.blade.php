@extends('layouts.admin')

@section('title', $programme ? 'Edit Programme' : 'New Programme')

@section('content')
<x-page-header :title="$programme ? 'Edit: '.$programme->title : 'Create Programme'" />

<form method="POST" action="{{ $programme ? route('admin.programmes.update', $programme) : route('admin.programmes.store') }}" class="max-w-2xl">
    @csrf
    @if ($programme) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Title" name="title" :value="$programme?->title" required />
            <x-form.input label="Title (বাংলা)" name="title_bn" :value="$programme?->title_bn" />
            <x-form.select label="Type" name="programme_type" :value="$programme?->programme_type ?? 'programme'" required
                           :options="['programme' => 'Programme', 'drama' => 'Drama', 'news' => 'News', 'event' => 'Event (e.g. Bhoot FM)', 'magazine' => 'Magazine', 'talk_show' => 'Talk Show']" />
            <x-form.select label="Station" name="station_id" :value="$programme?->station_id" placeholder="—" :options="$stations->all()" />
            <x-form.select label="Category" name="category_id" :value="$programme?->category_id" placeholder="—" :options="$categories->all()" />
            <div class="flex items-end pb-1"><x-form.toggle label="Published to public app" name="is_published" :checked="(bool) $programme?->is_published" /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$programme?->description" rows="3" /></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.programmes.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $programme ? 'Save Changes' : 'Create Programme' }}</button>
        </div>
    </div>
</form>
@endsection
