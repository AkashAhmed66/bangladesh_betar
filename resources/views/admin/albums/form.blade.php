@extends('layouts.admin')

@section('title', $album ? 'Edit Album' : 'New Album')

@section('content')
<x-page-header :title="$album ? 'Edit Album: '.$album->title : 'Create Album'" />

<form method="POST" action="{{ $album ? route('admin.albums.update', $album) : route('admin.albums.store') }}" class="max-w-2xl">
    @csrf
    @if ($album) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Title" name="title" :value="$album?->title" required />
            <x-form.input label="Title (বাংলা)" name="title_bn" :value="$album?->title_bn" />
            <x-form.select label="Type" name="album_type" :value="$album?->album_type ?? 'album'" required
                           :options="['album' => 'Album', 'film' => 'Film', 'compilation' => 'Compilation', 'single' => 'Single']" />
            <x-form.input label="Year" name="year" type="number" :value="$album?->year" />
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$album?->description" rows="3" /></div>
            <x-form.toggle label="Published" name="is_published" :checked="(bool) $album?->is_published" />
            <x-form.toggle label="Featured" name="is_featured" :checked="(bool) $album?->is_featured" />
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.albums.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $album ? 'Save Changes' : 'Create Album' }}</button>
        </div>
    </div>
</form>
@endsection
