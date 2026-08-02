@extends('layouts.admin')

@section('title', 'PDF to Speech')

@section('content')
<x-page-header title="PDF to Speech"
               subtitle="Convert PDFs or pasted text to spoken audio — English and Bangla, male or female voice. Fully offline (poppler · Tesseract OCR · espeak-ng)." />

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    {{-- ---- New conversion ---- --}}
    <div class="xl:col-span-1">
        <form method="POST" action="{{ route('admin.speech.store') }}" enctype="multipart/form-data" class="card"
              x-data="{ mode: 'pdf' }">
            @csrf
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">New Conversion</h3></div>
            <div class="card-body space-y-4">
                <x-form.input label="Title" name="title" :value="old('title')" required placeholder="e.g. Weekly bulletin — 2 Aug" />

                <div>
                    <label class="form-label">Source</label>
                    <div class="flex gap-1 rounded-lg border border-slate-200 p-0.5 dark:border-slate-700">
                        <button type="button" @click="mode = 'pdf'"
                                :class="mode === 'pdf' ? 'bg-primary-600 text-white' : 'text-slate-600 dark:text-slate-300'"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition">PDF file</button>
                        <button type="button" @click="mode = 'text'"
                                :class="mode === 'text' ? 'bg-primary-600 text-white' : 'text-slate-600 dark:text-slate-300'"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition">Paste text</button>
                    </div>
                </div>

                <div x-show="mode === 'pdf'">
                    <label class="form-label">PDF document</label>
                    <input type="file" name="pdf" accept=".pdf"
                           class="form-input mt-1 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium dark:file:bg-slate-800">
                    <p class="form-help">Digital PDFs read directly; scanned PDFs go through Bangla+English OCR automatically. Up to 50&nbsp;MB. Legacy Bijoy-encoded PDFs may extract garbled text — paste the text instead.</p>
                    @error('pdf')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div x-show="mode === 'text'" x-cloak>
                    <x-form.textarea label="Text" name="text" :value="old('text')" rows="7"
                                     help="English or Bangla — up to 120,000 characters." />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-form.select label="Language" name="language" :value="old('language', 'auto')" required
                                   :options="['auto' => 'Auto-detect', 'bn' => 'Bangla', 'en' => 'English']" />
                    <x-form.select label="Voice" name="voice" :value="old('voice', 'female')" required
                                   :options="['female' => 'Female', 'male' => 'Male']" />
                </div>
            </div>
            <div class="flex justify-end border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                <button type="submit" class="btn-primary"><x-icon name="megaphone" class="size-4" /> Convert to Speech</button>
            </div>
        </form>
    </div>

    {{-- ---- Conversions ---- --}}
    <div class="xl:col-span-2">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Conversions</h3></div>
            @forelse ($conversions as $conversion)
                <div class="border-t border-slate-100 px-5 py-4 first:border-0 dark:border-slate-800">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium text-slate-800 dark:text-slate-100">{{ $conversion->title }}</p>
                        <x-status-badge :status="$conversion->status" />
                        @if ($conversion->engine)
                            <span class="badge-{{ $conversion->engine === 'neural' ? 'green' : 'slate' }}">{{ $conversion->engine === 'neural' ? 'Neural voice' : 'Basic voice' }}</span>
                        @endif
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $conversion->language === 'bn' ? 'Bangla' : ($conversion->language === 'en' ? 'English' : 'Auto') }}
                            · {{ ucfirst($conversion->voice) }}
                            · {{ strtoupper($conversion->source_type) }}
                            @if ($conversion->used_ocr) · OCR @endif
                            @if ($conversion->duration_seconds) · {{ gmdate('i:s', $conversion->duration_seconds) }} @endif
                            @if ($conversion->characters) · {{ number_format($conversion->characters) }} chars @endif
                        </span>
                        <span class="ml-auto text-xs text-slate-400">{{ $conversion->created_at->diffForHumans() }} · {{ $conversion->user?->name }}</span>
                    </div>

                    @if ($conversion->status === 'failed' && $conversion->error)
                        <p class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $conversion->error }}</p>
                    @elseif ($conversion->isPending())
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            <x-icon name="clock" class="mr-1 inline size-4" />
                            {{ $conversion->status === 'extracting' ? 'Extracting text from the document…' : ($conversion->status === 'synthesizing' ? 'Synthesizing speech…' : 'Waiting in the queue…') }}
                        </p>
                    @endif

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        @if ($conversion->status === 'done' && $conversion->output_path)
                            <audio controls preload="none" class="h-9 max-w-full grow sm:grow-0 sm:min-w-80"
                                   src="{{ route('admin.speech.audio', $conversion) }}"></audio>
                            <a href="{{ route('admin.speech.audio', $conversion) }}" class="btn-secondary btn-sm">
                                <x-icon name="download" class="size-4" /> Download
                            </a>
                        @endif
                        <form method="POST" action="{{ route('admin.speech.destroy', $conversion) }}"
                              onsubmit="return confirm('Remove this conversion and its audio?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-ghost btn-sm text-rose-600 dark:text-rose-400"><x-icon name="trash" class="size-4" /> Remove</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-5 py-10"><x-empty-state icon="megaphone" title="No conversions yet" message="Upload a PDF or paste text on the left to generate spoken audio." /></div>
            @endforelse

            @if ($conversions->hasPages())
                <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">{{ $conversions->links() }}</div>
            @endif
        </div>
    </div>
</div>

@if ($conversions->contains(fn ($c) => $c->isPending()))
    {{-- A conversion is in flight — refresh so status/result appears without manual reloads. --}}
    <script>setTimeout(function () { window.location.reload(); }, 8000);</script>
@endif
@endsection
