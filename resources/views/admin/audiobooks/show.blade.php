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
            <form method="POST" action="{{ route('admin.audiobooks.submit', $book) }}">@csrf
                <button type="submit" class="btn-primary"><x-icon name="clipboard-check" class="size-4" /> Submit for Approval</button>
            </form>
        @endif
        <a href="{{ route('admin.audiobooks.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to Audio Books</a>
    </div>
</div>

@if ($book->status === 'failed' && $book->error)
    <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">{{ $book->error }}</div>
@elseif ($book->isPending())
    <div class="mb-6 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
        <x-icon name="clock" class="size-4" /> Generating both narrations… this page refreshes automatically.
    </div>
    <script>setTimeout(function () { window.location.reload(); }, 8000);</script>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">

        {{-- Both narrations --}}
        @if ($book->audio_male_path || $book->audio_female_path)
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Narrations</h3></div>
                <div class="card-body space-y-4">
                    @foreach (['male' => ['Male voice', $book->duration_male, $book->audio_male_path], 'female' => ['Female voice', $book->duration_female, $book->audio_female_path]] as $voice => [$label, $duration, $path])
                        @if ($path)
                            <div>
                                <p class="mb-1.5 flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                                    {{ $label }} <span class="text-xs font-normal text-slate-400">{{ gmdate($duration >= 3600 ? 'G:i:s' : 'i:s', $duration) }}</span>
                                </p>
                                <audio controls preload="none" class="h-10 w-full" src="{{ route('admin.audiobooks.audio', [$book, $voice]) }}"></audio>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- The text (what premium listeners read along) --}}
        @if ($book->text)
            <div class="card" x-data="{ fullText: false }">
                <div class="card-header">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Book Text</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400">{{ number_format($book->characters) }} characters @if ($book->used_ocr) · recovered by OCR @endif</span>
                        <button type="button" @click="fullText = ! fullText" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">
                            <span x-text="fullText ? 'Show less' : 'Show full'"></span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <p x-show="! fullText" class="{{ $book->language === 'bn' ? 'font-bangla' : '' }} whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ Str::limit($book->text, 800) }}</p>
                    <p x-show="fullText" x-cloak class="{{ $book->language === 'bn' ? 'font-bangla' : '' }} max-h-[32rem] overflow-y-auto whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $book->text }}</p>
                </div>
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
                    'Engine' => $book->engine ? ($book->engine === 'neural' ? 'Neural (Piper/MMS)' : 'espeak-ng') : null,
                    'Male narration' => $book->duration_male ? gmdate('i:s', $book->duration_male) : null,
                    'Female narration' => $book->duration_female ? gmdate('i:s', $book->duration_female) : null,
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
