@extends('layouts.admin')

@section('title', $book->title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">{{ $book->title }}</h2>
            <x-status-badge :status="$book->status" />
            @if ($book->engine)
                <span class="badge-{{ $book->engine === 'neural' ? 'green' : 'slate' }}">{{ $book->engine === 'neural' ? 'Neural voice' : 'Basic voice' }}</span>
            @endif
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Audio book · {{ $book->language === 'bn' ? 'Bangla' : 'English' }} · by {{ $book->user?->name }}
            · {{ $book->created_at->format('j M Y H:i') }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if ($book->isReadyForSubmission() && ($book->user_id === auth()->id() || auth()->user()->can('records.view-all')))
            <form method="POST" action="{{ route('admin.audiobooks.submit', $book) }}"
                  onsubmit="return confirm('Submit this audio book for publication? Approvers will review it.');">
                @csrf
                <button type="submit" class="btn-primary"><x-icon name="clipboard-check" class="size-4" /> Submit for Publication</button>
            </form>
        @endif
        @if ($book->status === 'published')
            @can('audiobooks.approve')
                <form method="POST" action="{{ route('admin.audiobooks.unpublish', $book) }}"
                      onsubmit="return confirm('Remove this audio book from the public app?');">
                    @csrf
                    <button type="submit" class="btn-danger"><x-icon name="x" class="size-4" /> Unpublish</button>
                </form>
            @endcan
        @endif
        <a href="{{ route('admin.audiobooks.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to Audio Books</a>
    </div>
</div>

@if ($book->status === 'failed' && $book->error)
    <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">{{ $book->error }}</div>
@elseif ($book->isPending())
    <div class="mb-6 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
        <x-icon name="clock" class="size-4" /> Generating the narration… this page refreshes automatically.
    </div>
    <script>setTimeout(function () { window.location.reload(); }, 8000);</script>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">

        {{-- Narrations — whichever voices were generated --}}
        @if ($book->audio_male_path || $book->audio_female_path || $book->audio_enhanced_path)
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Narrations</h3></div>
                <div class="card-body space-y-4">
                    @foreach (['male' => ['Male voice', $book->duration_male, $book->audio_male_path], 'female' => ['Female voice', $book->duration_female, $book->audio_female_path], 'enhanced' => ['Enhanced', $book->duration_enhanced, $book->audio_enhanced_path]] as $voice => [$label, $duration, $path])
                        @if ($path)
                            <div>
                                <p class="mb-1.5 flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                                    {{ $label }} <span class="text-xs font-normal text-slate-400">{{ gmdate($duration >= 3600 ? 'G:i:s' : 'i:s', $duration) }}</span>
                                </p>
                                <audio controls controlslist="nodownload" oncontextmenu="return false" preload="none" class="h-10 w-full"
                                       data-hls="{{ \App\Support\Hls::adminBookHls($book, $voice) ?? '' }}"
                                       data-fallback="{{ route('admin.audiobooks.audio', [$book, $voice]) }}"
                                       x-init="window.betarHls($el)"></audio>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- The text (what premium listeners read along) — editable between
             generations: saving regenerates the narration and the book must
             pass approval again before publishing. --}}
        @php
            // Editable in every state except mid-generation; the approval
            // flow is preserved — editing always routes back through Submit.
            $canEditText = $book->status !== 'generating'
                && ($book->user_id === auth()->id() || auth()->user()->can('records.view-all'));
            $editWarning = match ($book->status) {
                'published' => 'This book is LIVE — saving takes it off the public app until it is approved again. Continue?',
                'pending_approval' => 'This withdraws the pending approval request — the book must be resubmitted after regeneration. Continue?',
                default => 'Save the edited text and regenerate the narration? The book must be approved again before it is published.',
            };
        @endphp
        @if ($book->text || $canEditText)
            <div class="card" x-data="{ fullText: false, editing: {{ $errors->has('text') ? 'true' : 'false' }} }">
                <div class="card-header">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Book Text</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400">{{ number_format((int) $book->characters) }} characters @if ($book->used_ocr) · recovered by OCR @endif @if ($book->text_edited) · edited @endif</span>
                        <button type="button" x-show="! editing" @click="fullText = ! fullText" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">
                            <span x-text="fullText ? 'Show less' : 'Show full'"></span>
                        </button>
                        @if ($canEditText)
                            <button type="button" x-show="! editing" @click="editing = true"
                                    class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">Edit text</button>
                        @endif
                    </div>
                </div>
                <div class="card-body" x-show="! editing">
                    <p x-show="! fullText" class="{{ $book->language === 'bn' ? 'font-bangla' : '' }} whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ Str::limit($book->text, 800) }}</p>
                    <p x-show="fullText" x-cloak class="{{ $book->language === 'bn' ? 'font-bangla' : '' }} max-h-[32rem] overflow-y-auto whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $book->text }}</p>
                </div>
                @if ($canEditText)
                    <form method="POST" action="{{ route('admin.audiobooks.update-text', $book) }}" x-show="editing" x-cloak
                          onsubmit="return confirm({{ Illuminate\Support\Js::from($editWarning) }});">
                        @csrf
                        <div class="card-body">
                            <textarea name="text" rows="18" required minlength="5" maxlength="120000"
                                      class="{{ $book->language === 'bn' ? 'font-bangla' : '' }} w-full rounded-lg border-slate-300 bg-white text-sm leading-relaxed text-slate-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">{{ old('text', $book->text) }}</textarea>
                            @error('text')<p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Saving regenerates the narration from this text (the original document is no longer used) and the book must be submitted for approval again.
                            </p>
                        </div>
                        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                            <button type="button" @click="editing = false" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary"><x-icon name="arrow-path" class="size-4" /> Save &amp; Regenerate Narration</button>
                        </div>
                    </form>
                @endif
            </div>
        @endif

        {{-- Decision --}}
        @can('audiobooks.approve')
            @if ($book->status === 'pending_approval')
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Your Decision</h3></div>
                    <form method="POST" action="{{ route('admin.audiobooks.review', $book) }}">
                        @csrf
                        <div class="card-body">
                            <x-form.textarea label="Remarks" name="comments" rows="3" :required="true"
                                             help="Required for every decision — recorded on the audio book and in the audit trail." />
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                            <button type="submit" name="action" value="reject" class="btn-danger"
                                    onclick="return confirm('Reject this audio book? The creator can revise and resubmit.');">
                                <x-icon name="x" class="size-4" /> Reject
                            </button>
                            <button type="submit" name="action" value="approve" class="btn-primary">
                                <x-icon name="check-badge" class="size-4" /> Approve &amp; Publish to Premium
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        @endcan

        @if ($book->review_comments)
            <div class="card"><div class="card-body text-sm text-slate-600 dark:text-slate-300">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Reviewer remarks</p>
                “{{ $book->review_comments }}”
                @if ($book->approver) — {{ $book->approver->name }}@endif
                @if ($book->approved_at) ({{ $book->approved_at->diffForHumans() }}) @endif
            </div></div>
        @endif
    </div>

    {{-- Right rail --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Details</h3></div>
            <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                @foreach ([
                    'Language' => $book->language === 'bn' ? 'Bangla' : 'English',
                    'Source' => strtoupper($book->source_type).($book->used_ocr ? ' (OCR)' : ''),
                    'Engine' => $book->engine ? ($book->engine === 'neural' ? 'Neural (TTS API)' : 'espeak-ng') : null,
                    'Male narration' => $book->duration_male ? gmdate('i:s', $book->duration_male) : null,
                    'Female narration' => $book->duration_female ? gmdate('i:s', $book->duration_female) : null,
                    'Enhanced narration' => $book->duration_enhanced ? gmdate('i:s', $book->duration_enhanced) : null,
                    'Characters' => $book->characters ? number_format($book->characters) : null,
                    'Created by' => $book->user?->name,
                    'Created' => $book->created_at->format('j M Y H:i'),
                    'Submitted' => $book->submitted_at?->format('j M Y H:i'),
                    'Decided by' => $book->approver?->name,
                    'Published' => $book->published_at?->format('j M Y H:i'),
                ] as $label => $value)
                    @if ($value)
                        <div class="flex justify-between gap-3 px-5 py-2.5">
                            <dt class="text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                            <dd class="text-right font-medium text-slate-700 dark:text-slate-200">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
        </div>

        <div class="card"><div class="card-body text-xs leading-relaxed text-slate-500 dark:text-slate-400">
            <p class="mb-1 font-semibold text-slate-600 dark:text-slate-300">Publishing flow</p>
            Generate (both voices) → Submit for approval → an audiobook approver reviews on this page → on approval the book goes live in the public app, <span class="font-medium">premium listeners only</span>, with read-along text.
        </div></div>
    </div>
</div>
@endsection
