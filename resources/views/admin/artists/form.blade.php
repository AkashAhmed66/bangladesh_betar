@extends('layouts.admin')

@section('title', $artist ? 'Edit Artist' : 'New Artist')

@section('content')
<x-page-header :title="$artist ? 'Edit Artist: '.$artist->name : 'Create Artist Profile'"
               subtitle="Public artist pages let listeners follow artists (FR-SNG-02, FR-PUB-12)" />

<form method="POST" action="{{ $artist ? route('admin.artists.update', $artist) : route('admin.artists.store') }}" class="max-w-2xl">
    @csrf
    @if ($artist) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Name" name="name" :value="$artist?->name" required />
            <x-form.input label="Name (বাংলা)" name="name_bn" :value="$artist?->name_bn" />
            <x-form.select label="Primary type" name="artist_type" :value="$artist?->artist_type ?? 'singer'" required
                           :options="collect($types)->mapWithKeys(fn ($t) => [$t => ucfirst(str_replace('_', ' ', $t))])->all()" />
            <div class="flex flex-col justify-end gap-3 pb-1">
                <x-form.toggle label="Featured artist" name="is_featured" :checked="(bool) $artist?->is_featured" help="Eligible for homepage spotlight (FR-CUR-05)." />
                <x-form.toggle label="Published (visible on public app)" name="is_published" :checked="$artist ? (bool) $artist->is_published : true" />
            </div>
            <div class="sm:col-span-2"><x-form.textarea label="Biography (English)" name="bio" :value="$artist?->bio" rows="3" /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Biography (বাংলা)" name="bio_bn" :value="$artist?->bio_bn" rows="3" /></div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.artists.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $artist ? 'Save Changes' : 'Create Artist' }}</button>
        </div>
    </div>
</form>
@endsection
