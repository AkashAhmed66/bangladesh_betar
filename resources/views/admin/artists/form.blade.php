@extends('layouts.admin')

@section('title', $artist ? 'Edit Artist' : 'New Artist')

@section('content')
@php
    $photoUrl = $artist && $artist->photo_path ? asset('storage/'.$artist->photo_path) : null;
    $coverUrl = $artist && $artist->cover_path ? asset('storage/'.$artist->cover_path) : null;
@endphp

<x-page-header :title="$artist ? 'Edit Artist: '.$artist->name : 'Create Artist Profile'"
               subtitle="Public artist pages let listeners follow artists (FR-SNG-02, FR-PUB-12)" />

<form method="POST" action="{{ $artist ? route('admin.artists.update', $artist) : route('admin.artists.store') }}"
      enctype="multipart/form-data" class="max-w-3xl space-y-6"
      x-data="{
          photoPreview: @js($photoUrl),
          coverPreview: @js($coverUrl),
          pick(e, target) { const f = e.target.files[0]; if (f) this[target] = URL.createObjectURL(f); }
      }">
    @csrf
    @if ($artist) @method('PUT') @endif

    @if ($artist && $artist->user)
        <div class="flex items-center gap-2 rounded-(--radius-app) border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300">
            <x-icon name="user-circle" class="size-4.5 shrink-0" />
            <span>Linked to user account <strong>{{ $artist->user->name }}</strong> ({{ $artist->user->email }}). They can edit this profile from their own account.</span>
        </div>
    @endif

    {{-- Images ------------------------------------------------------------}}
    <div class="card overflow-hidden">
        <div class="relative h-40 w-full overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-sky-700 sm:h-48">
            <img :src="coverPreview" x-show="coverPreview" x-cloak class="size-full object-cover" alt="Cover">
            <label class="absolute right-3 top-3 inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-black/40 px-3 py-1.5 text-xs font-medium text-white backdrop-blur transition hover:bg-black/55">
                <x-icon name="upload" class="size-3.5" /> Cover banner
                <input type="file" name="cover" accept="image/*" class="hidden" @change="pick($event, 'coverPreview')">
            </label>
        </div>
        <div class="flex flex-wrap items-end gap-4 px-5 pb-5 -mt-12">
            <div class="relative shrink-0">
                <div class="flex size-24 items-center justify-center overflow-hidden rounded-full bg-primary-700 ring-4 ring-white dark:ring-slate-900">
                    <img :src="photoPreview" x-show="photoPreview" x-cloak class="size-full object-cover" alt="Photo">
                    <span x-show="!photoPreview" class="text-3xl font-bold text-white">{{ strtoupper(mb_substr($artist->name ?? 'A', 0, 1)) }}</span>
                </div>
                <label class="absolute -bottom-1 -right-1 cursor-pointer rounded-full bg-white p-2 shadow-md ring-1 ring-slate-200 transition hover:bg-slate-50 dark:bg-slate-800 dark:ring-slate-700"
                       title="Change photo">
                    <x-icon name="pencil" class="size-3.5 text-slate-600 dark:text-slate-300" />
                    <input type="file" name="photo" accept="image/*" class="hidden" @change="pick($event, 'photoPreview')">
                </label>
            </div>
            <p class="pb-2 text-xs text-slate-400">JPG, PNG or WebP. Photo up to 4 MB, cover up to 8 MB.</p>
        </div>
    </div>

    {{-- Details -----------------------------------------------------------}}
    <div class="card">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Name" name="name" :value="$artist?->name" required />
            <x-form.input label="Name (বাংলা)" name="name_bn" :value="$artist?->name_bn" />
            <x-form.select label="Primary type" name="artist_type" :value="$artist?->artist_type ?? 'singer'" required
                           :options="collect($types)->mapWithKeys(fn ($t) => [$t => ucfirst(str_replace('_', ' ', $t))])->all()" />
            <div class="flex flex-col justify-end gap-3 pb-1">
                <x-form.toggle label="Featured artist" name="is_featured" :checked="(bool) $artist?->is_featured" help="Eligible for homepage spotlight (FR-CUR-05)." />
                <x-form.toggle label="Verified badge" name="is_verified" :checked="(bool) $artist?->is_verified" help="Shows a verified checkmark on the public profile." />
                <x-form.toggle label="Published (visible on public app)" name="is_published" :checked="$artist ? (bool) $artist->is_published : true" />
            </div>
            <div class="sm:col-span-2"><x-form.textarea label="Biography (English)" name="bio" :value="$artist?->bio" rows="3" /></div>
            <div class="sm:col-span-2"><x-form.textarea label="Biography (বাংলা)" name="bio_bn" :value="$artist?->bio_bn" rows="3" /></div>

            <div class="sm:col-span-2">
                <p class="form-label mb-2">Social &amp; streaming links</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ([
                        'website' => ['Website', 'globe'],
                        'youtube' => ['YouTube', 'play'],
                        'facebook' => ['Facebook', 'users'],
                        'instagram' => ['Instagram', 'eye'],
                        'twitter' => ['X / Twitter', 'chat'],
                        'spotify' => ['Spotify', 'music'],
                    ] as $key => [$label, $icon])
                        <div>
                            <label for="social_{{ $key }}" class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-600 dark:text-slate-300">
                                <x-icon name="{{ $icon }}" class="size-3.5 text-slate-400" /> {{ $label }}
                            </label>
                            <input id="social_{{ $key }}" name="social[{{ $key }}]" type="url"
                                   value="{{ old('social.'.$key, data_get($artist, 'social_links.'.$key)) }}"
                                   placeholder="https://…" class="form-input">
                            @error('social.'.$key)<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.artists.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $artist ? 'Save Changes' : 'Create Artist' }}</button>
        </div>
    </div>
</form>
@endsection
