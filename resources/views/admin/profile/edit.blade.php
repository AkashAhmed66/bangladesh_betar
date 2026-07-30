@extends('layouts.admin')

@section('title', 'My Profile')

@section('content')
@php
    $initial = strtoupper(mb_substr($user->name, 0, 1));
    $roleName = $user->getRoleNames()->first();
    $coverUrl = $artist && $artist->cover_path ? asset('storage/'.$artist->cover_path) : null;
@endphp

<x-page-header title="My Profile"
               subtitle="Manage your account details and how you appear across Bangladesh Betar." />

<form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-6"
      x-data="{
          photoPreview: @js($user->avatarUrl()),
          coverPreview: @js($coverUrl),
          pick(e, target) { const f = e.target.files[0]; if (f) this[target] = URL.createObjectURL(f); }
      }">
    @csrf
    @method('PUT')

    {{-- Identity header — cover banner (artists) + avatar ------------------}}
    <div class="card overflow-hidden">
        @if ($artist)
            <div class="relative h-40 w-full overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-sky-700 sm:h-52">
                <img :src="coverPreview" x-show="coverPreview" x-cloak class="size-full object-cover" alt="Cover banner">
                <label class="absolute right-3 top-3 inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-black/40 px-3 py-1.5 text-xs font-medium text-white backdrop-blur transition hover:bg-black/55">
                    <x-icon name="upload" class="size-3.5" /> Change cover
                    <input type="file" name="cover" accept="image/*" class="hidden" @change="pick($event, 'coverPreview')">
                </label>
            </div>
        @endif

        <div class="flex flex-wrap items-end gap-4 px-5 pb-5 {{ $artist ? '-mt-12' : 'pt-5' }}">
            {{-- Avatar with change control --}}
            <div class="relative shrink-0">
                <div class="flex size-24 items-center justify-center overflow-hidden rounded-full bg-primary-700 ring-4 ring-white dark:ring-slate-900">
                    <img :src="photoPreview" x-show="photoPreview" x-cloak class="size-full object-cover" alt="Profile photo">
                    <span x-show="!photoPreview" class="text-3xl font-bold text-white">{{ $initial }}</span>
                </div>
                <label class="absolute -bottom-1 -right-1 cursor-pointer rounded-full bg-white p-2 shadow-md ring-1 ring-slate-200 transition hover:bg-slate-50 dark:bg-slate-800 dark:ring-slate-700 dark:hover:bg-slate-700"
                       title="Change photo">
                    <x-icon name="pencil" class="size-3.5 text-slate-600 dark:text-slate-300" />
                    <input type="file" name="photo" accept="image/*" class="hidden" @change="pick($event, 'photoPreview')">
                </label>
            </div>

            <div class="min-w-0 flex-1 pb-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="truncate text-xl font-bold text-slate-900 dark:text-white">{{ $user->name }}</h2>
                    @if ($artist && $artist->is_verified)
                        <span class="badge-blue"><x-icon name="check-badge" class="size-3.5" /> Verified</span>
                    @endif
                    <span class="badge-slate">{{ $roleName }}</span>
                </div>
                <p class="mt-0.5 truncate text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                @if ($artist)
                    <p class="mt-1 inline-flex items-center gap-1 text-xs text-slate-400">
                        <x-icon name="user-circle" class="size-3.5" />
                        Public artist profile · {{ ucfirst(str_replace('_', ' ', $artist->artist_type ?? 'artist')) }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Account details ---------------------------------------------------}}
    <div class="card">
        <div class="card-header">
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                <x-icon name="user-circle" class="size-4 text-primary-500" /> Account details
            </h3>
        </div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Full name" name="name" :value="$user->name" required />
            <x-form.input label="E-mail" name="email" type="email" :value="$user->email" required />
            <x-form.input label="Phone" name="phone" :value="$user->phone" />
            <x-form.select label="Language" name="locale" :value="$user->locale ?? 'en'"
                           :options="['en' => 'English', 'bn' => 'বাংলা (Bengali)']" />
            <div class="sm:col-span-2">
                <x-form.textarea label="Short bio" name="bio" :value="$user->bio" rows="3"
                                 help="A short introduction shown on your profile." />
            </div>
        </div>
    </div>

    {{-- Artist profile (artist accounts only) -----------------------------}}
    @if ($artist)
        <div class="card">
            <div class="card-header">
                <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                    <x-icon name="microphone" class="size-4 text-primary-500" /> Artist profile
                </h3>
                @if ($artist->is_verified)
                    <span class="badge-blue"><x-icon name="check-badge" class="size-3.5" /> Verified artist</span>
                @else
                    <span class="text-xs text-slate-400">Verification is set by an administrator</span>
                @endif
            </div>
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.select label="Artist type" name="artist_type" :value="$artist->artist_type ?? 'singer'"
                               :options="collect($artistTypes)->mapWithKeys(fn ($t) => [$t => ucfirst(str_replace('_', ' ', $t))])->all()" />
                <x-form.input label="Name (বাংলা)" name="name_bn" :value="$artist->name_bn" />
                <div class="sm:col-span-2">
                    <x-form.textarea label="Biography (বাংলা)" name="bio_bn" :value="$artist->bio_bn" rows="3" />
                </div>

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
        </div>
    @endif

    {{-- Security ----------------------------------------------------------}}
    <div class="card">
        <div class="card-header">
            <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                <x-icon name="shield-check" class="size-4 text-primary-500" /> Security
            </h3>
            <span class="text-xs text-slate-400">Leave blank to keep your current password</span>
        </div>
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-form.input label="Current password" name="current_password" type="password" autocomplete="current-password" />
            <x-form.input label="New password" name="password" type="password" autocomplete="new-password" help="Minimum 6 characters." />
            <x-form.input label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" />
        </div>
    </div>

    <div class="flex items-center justify-end gap-2">
        <a href="{{ url()->previous() }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary"><x-icon name="check-badge" class="size-4" /> Save Changes</button>
    </div>
</form>
@endsection
