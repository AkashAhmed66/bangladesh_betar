@extends('layouts.admin')

@section('title', $song ? 'Edit Song' : 'New Song')

@section('content')
<x-page-header :title="$song ? 'Edit Song' : 'Create Song Record'"
               subtitle="Links a song's metadata, artists and version family to an audio asset (FR-SNG-01/03)" />

<form method="POST" action="{{ $song ? route('admin.songs.update', $song) : route('admin.songs.store') }}" class="max-w-4xl space-y-5">
    @csrf
    @if ($song) @method('PUT') @endif

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Song & Classification</h3></div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.select label="Audio asset" name="audio_asset_id" :value="$song?->audio_asset_id" required placeholder="Select a song asset…" :options="$assets->all()" />
            <x-form.select label="Album" name="album_id" :value="$song?->album_id" placeholder="—" :options="$albums->all()" />
            <x-form.input label="Track number" name="track_number" type="number" :value="$song?->track_number" />
            <x-form.input label="Release year" name="release_year" type="number" :value="$song?->release_year" />
            <x-form.select label="Genre" name="genre_id" :value="$song?->genre_id" placeholder="—" :options="$genres->all()" />
            <x-form.select label="Mood" name="mood_id" :value="$song?->mood_id" placeholder="—" :options="$moods->all()" />
            <x-form.select label="Version type" name="version_type" :value="$song?->version_type ?? 'original'" required
                           :options="['original' => 'Original', 'live' => 'Live', 'remastered' => 'Remastered', 'instrumental' => 'Instrumental', 'cover' => 'Cover']" />
            <x-form.select label="Master song (version family)" name="master_song_id" :value="$song?->master_song_id" placeholder="This is the master" :options="$masterSongs->all()" />
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Credits</h3></div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-form.select label="Singer" name="singer_id" :value="$song?->artists->where('pivot.role', 'singer')->first()?->id" placeholder="—" :options="$singers->all()" />
            <x-form.select label="Composer" name="composer_id" :value="$song?->artists->where('pivot.role', 'composer')->first()?->id" placeholder="—" :options="$composers->all()" />
            <x-form.select label="Lyricist" name="lyricist_id" :value="$song?->artists->where('pivot.role', 'lyricist')->first()?->id" placeholder="—" :options="$lyricists->all()" />
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Lyrics</h3></div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.textarea label="Lyrics (English/Transliteration)" name="lyrics" :value="$song?->lyrics" rows="6" />
            <x-form.textarea label="Lyrics (বাংলা)" name="lyrics_bn" :value="$song?->lyrics_bn" rows="6" />
            <div class="sm:col-span-2 flex gap-6">
                <x-form.toggle label="Mood/genre verified" name="mood_genre_verified" :checked="(bool) $song?->mood_genre_verified" help="AI classifications need librarian verification (FR-SNG-10)." />
                <x-form.toggle label="Featured song" name="is_featured" :checked="(bool) $song?->is_featured" />
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('admin.songs.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">{{ $song ? 'Save Changes' : 'Create Song' }}</button>
    </div>
</form>
@endsection
