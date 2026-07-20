@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<div class="flex min-h-full">

    {{-- Brand panel --}}
    <div class="relative hidden w-1/2 overflow-hidden bg-primary-950 lg:block">
        <div class="absolute inset-0 bg-gradient-to-br from-primary-900 via-primary-950 to-slate-950"></div>

        {{-- Decorative waveform --}}
        <svg class="absolute inset-x-0 bottom-0 h-64 w-full opacity-25" preserveAspectRatio="none" viewBox="0 0 1200 300">
            @for ($i = 0; $i < 120; $i++)
                @php $h = 20 + 130 * abs(sin($i / 7) * cos($i / 3.1)); @endphp
                <rect x="{{ $i * 10 }}" y="{{ 150 - $h / 2 }}" width="4" height="{{ $h }}" rx="2" fill="var(--accent-500)" />
            @endfor
        </svg>

        <div class="relative flex h-full flex-col justify-between p-12">
            <div class="flex items-center gap-3">
                <div class="flex size-12 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20 backdrop-blur">
                    <x-icon name="radio" class="size-7 text-accent-400" />
                </div>
                <div>
                    <p class="text-lg font-semibold text-white">{{ \App\Support\Theme::brand('full_name') }}</p>
                    <p class="text-sm text-primary-300">বাংলাদেশ বেতার অডিও আর্কাইভ</p>
                </div>
            </div>

            <div class="max-w-md">
                <h1 class="text-4xl font-bold leading-tight text-white">
                    The sound heritage<br>of the nation<span class="text-accent-400">.</span>
                </h1>
                <p class="mt-4 text-primary-200/90">
                    Digitize, preserve, manage and publish decades of songs, programmes,
                    dramas and historic broadcasts — from a single, rights-controlled archive.
                </p>

                <dl class="mt-10 grid grid-cols-3 gap-6 border-t border-white/10 pt-6">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-primary-300">Modules</dt>
                        <dd class="mt-1 text-2xl font-semibold text-white">27</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-primary-300">Stations</dt>
                        <dd class="mt-1 text-2xl font-semibold text-white">5+</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-primary-300">Capacity</dt>
                        <dd class="mt-1 text-2xl font-semibold text-white">1M+</dd>
                    </div>
                </dl>
            </div>

            <p class="text-xs text-primary-400">© {{ date('Y') }} Bangladesh Betar — Government of the People's Republic of Bangladesh</p>
        </div>
    </div>

    {{-- Form panel --}}
    <div class="flex w-full items-center justify-center bg-slate-100 px-6 py-12 dark:bg-slate-950 lg:w-1/2">
        <div class="w-full max-w-sm">

            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="flex size-11 items-center justify-center rounded-xl bg-primary-700">
                    <x-icon name="radio" class="size-6 text-white" />
                </div>
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white">{{ \App\Support\Theme::brand('full_name') }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Admin Portal</p>
                </div>
            </div>

            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Sign in to the Admin Portal</h2>
            <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">Staff access only. Use your Bangladesh Betar account.</p>

            @if ($errors->any())
                <div class="mt-5 flex items-start gap-2.5 rounded-(--radius-app) border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                    <x-icon name="exclamation" class="mt-0.5 size-4 shrink-0" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="form-label">E-mail address</label>
                    <input id="email" name="email" type="email" required autofocus autocomplete="username"
                           value="{{ old('email') }}" placeholder="you@betar.gov.bd" class="form-input">
                </div>

                <div x-data="{ show: false }">
                    <label for="password" class="form-label">Password</label>
                    <div class="relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required
                               autocomplete="current-password" placeholder="••••••••" class="form-input pr-10">
                        <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <x-icon name="eye" class="size-4.5" />
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" name="remember"
                               class="size-4 rounded border-slate-300 text-primary-700 focus:ring-primary-600 dark:border-slate-600 dark:bg-slate-800">
                        Remember me
                    </label>
                    <span class="text-sm text-slate-400 dark:text-slate-500">Forgot? Contact the administrator</span>
                </div>

                <button type="submit" class="btn-primary w-full py-2.5">
                    <x-icon name="logout" class="size-4.5 rotate-180" />
                    Sign in
                </button>
            </form>

            <div class="mt-8 rounded-(--radius-app) border border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                <p class="font-medium text-slate-600 dark:text-slate-300">Demo accounts (password: <code class="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">123456</code>)</p>
                <p class="mt-1">admin@betar.gov.bd · curator@betar.gov.bd · moderator@betar.gov.bd · approver@betar.gov.bd</p>
            </div>
        </div>
    </div>
</div>
@endsection
