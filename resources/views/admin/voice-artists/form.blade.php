@extends('layouts.admin')

@section('title', $voiceArtist ? 'Edit Voice Artist' : 'New Voice Artist')

@section('content')
<x-page-header :title="$voiceArtist ? 'Edit: '.$voiceArtist->name : 'New Voice Artist'"
               subtitle="Voice talent profile for marketing casting searches (FR-MKT-01)" />

<form method="POST" action="{{ $voiceArtist ? route('admin.voice-artists.update', $voiceArtist) : route('admin.voice-artists.store') }}" class="max-w-3xl">
    @csrf
    @if ($voiceArtist) @method('PUT') @endif

    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Name" name="name" :value="$voiceArtist?->name" required />
            <x-form.select label="Linked artist profile" name="artist_id" :value="$voiceArtist?->artist_id" placeholder="— none —"
                           :options="$artists->all()" help="Optionally link to an existing person record." />
            <x-form.select label="Gender" name="gender" :value="$voiceArtist?->gender" placeholder="—"
                           :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']" />
            <x-form.select label="Age range" name="age_range" :value="$voiceArtist?->age_range" placeholder="—"
                           :options="['child' => 'Child', 'young' => 'Young', 'adult' => 'Adult', 'senior' => 'Senior']" />
            <x-form.input label="Languages" name="languages" :value="$voiceArtist?->languages" help="Comma separated, e.g. Bangla, English" />
            <x-form.input label="Accent" name="accent" :value="$voiceArtist?->accent" help="e.g. Standard, Chittagong, Sylheti" />
            <x-form.input label="Tone" name="tone" :value="$voiceArtist?->tone" help="e.g. Warm, Authoritative, Energetic" />
            <x-form.input label="Style" name="style" :value="$voiceArtist?->style" help="e.g. Commercial, Narration, Promo" />
            <div class="sm:col-span-2"><x-form.textarea label="Notes" name="notes" :value="$voiceArtist?->notes" rows="2" /></div>
            <div class="sm:col-span-2">
                <x-form.toggle label="Available for casting" name="is_available" :checked="(bool) ($voiceArtist?->is_available ?? true)"
                               help="Show this artist as bookable in casting searches." />
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.voice-artists.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $voiceArtist ? 'Save Changes' : 'Add Voice Artist' }}</button>
        </div>
    </div>
</form>
@endsection
