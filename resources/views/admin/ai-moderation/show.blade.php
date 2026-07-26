@extends('layouts.admin')

@section('title', $asset->title)

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="page-title">{{ $asset->title }}</h2>
            <x-status-badge :status="$asset->status" />
        </div>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ $asset->archive_no }} · Uploaded by {{ $asset->uploader?->name ?? '—' }}
            @if ($asset->title_bn) · {{ $asset->title_bn }} @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.assets.show', $asset) }}" class="btn-secondary"><x-icon name="archive" class="size-4" /> Full Asset Record</a>
        <a href="{{ route('admin.ai-moderation.index') }}" class="btn-secondary"><x-icon name="chevron-left" class="size-4" /> Back to Queue</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">

        @if (! $job)
            <div class="card"><div class="card-body"><x-empty-state icon="shield" title="No analysis on record" message="This asset has no AI analysis job associated with it." /></div></div>
        @else
            {{-- Duplicate detection --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">Duplicate Detection</h3>
                    @if ($job->is_duplicate)<span class="badge-amber">Possible duplicate</span>@else<span class="badge-green">No match</span>@endif
                </div>
                <div class="card-body">
                    @if ($job->is_duplicate)
                        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Audio match</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $job->audio_match_pct !== null ? number_format($job->audio_match_pct, 1).'%' : '—' }}</p></div>
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Content match</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $job->content_match_pct !== null ? number_format($job->content_match_pct, 1).'%' : '—' }}</p></div>
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Matched upload</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $job->matched_filename ?? '—' }}</p></div>
                            <div><p class="text-xs text-slate-500 dark:text-slate-400">Uploaded</p><p class="font-medium text-slate-800 dark:text-slate-100">{{ $job->matched_uploaded_at?->format('j M Y H:i') ?? '—' }}</p></div>
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">No acoustic or content match against the existing corpus.</p>
                    @endif
                </div>
            </div>

            {{-- Content safety --}}
            <div class="card">
                <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Content Safety Analysis</h3></div>
                <div class="card-body space-y-5">
                    @if ($job->summary)
                        <p class="rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">{{ $job->summary }}</p>
                    @endif

                    <div>
                        <div class="mb-1.5 flex items-center gap-2">
                            @if ($job->violence_detected)<span class="badge-red">Violence detected</span>@else<span class="badge-green">No violence detected</span>@endif
                            @if ($job->violence_confidence !== null)<span class="text-xs text-slate-400">confidence {{ number_format($job->violence_confidence * 100) }}%</span>@endif
                        </div>
                        @foreach (($job->violence_evidence ?? []) as $quote)
                            <p class="font-bangla mt-1 rounded-lg border-l-2 border-rose-300 bg-rose-50/60 px-3 py-1.5 text-sm text-slate-600 dark:border-rose-500/40 dark:bg-rose-500/5 dark:text-slate-300">&ldquo;{{ $quote }}&rdquo;</p>
                        @endforeach
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center gap-2">
                            @if ($job->anti_government_detected)<span class="badge-red">Anti-government content detected</span>@else<span class="badge-green">No anti-government content detected</span>@endif
                            @if ($job->anti_government_confidence !== null)<span class="text-xs text-slate-400">confidence {{ number_format($job->anti_government_confidence * 100) }}%</span>@endif
                        </div>
                        @foreach (($job->anti_government_evidence ?? []) as $quote)
                            <p class="font-bangla mt-1 rounded-lg border-l-2 border-rose-300 bg-rose-50/60 px-3 py-1.5 text-sm text-slate-600 dark:border-rose-500/40 dark:bg-rose-500/5 dark:text-slate-300">&ldquo;{{ $quote }}&rdquo;</p>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Transcript --}}
            @if ($job->transcript_readable || $job->transcript)
                <div class="card">
                    <div class="card-header">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Transcript</h3>
                        <span class="text-xs text-slate-400">{{ $job->language ? ucfirst($job->language) : 'Language unknown' }}@if ($job->duration_sec) · {{ gmdate('i:s', (int) $job->duration_sec) }} @endif</span>
                    </div>
                    <div class="card-body">
                        <p class="font-bangla whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $job->transcript_readable ?: $job->transcript }}</p>
                    </div>
                </div>
            @endif
        @endif

        {{-- Decision --}}
        @can('ai-moderation.review')
            @if ($asset->status === 'ai_flagged')
                <div class="card">
                    <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">AI Reviewer Decision</h3></div>
                    <form method="POST" action="{{ route('admin.ai-moderation.review', $asset) }}">
                        @csrf
                        <div class="card-body">
                            <x-form.textarea label="Remarks" name="comments" rows="3" :required="true"
                                             help="Required for every decision — explain why this asset is accepted or rejected." />
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                            <button type="submit" name="action" value="reject" class="btn-danger"
                                    onclick="return confirm('Reject this asset? It can never be submitted for approval or published.');">
                                <x-icon name="x" class="size-4" /> Reject
                            </button>
                            <button type="submit" name="action" value="approve" class="btn-primary"><x-icon name="check-badge" class="size-4" /> Approve &amp; Release to Pipeline</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="card"><div class="card-body text-sm text-slate-500 dark:text-slate-400">
                    This asset has already been reviewed
                    @if ($job?->reviewer) by <span class="font-medium text-slate-700 dark:text-slate-200">{{ $job->reviewer->name }}</span> @endif
                    @if ($job?->reviewed_at) ({{ $job->reviewed_at->diffForHumans() }}) @endif.
                    @if ($job?->review_comments)
                        <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60">{{ $job->review_comments }}</p>
                    @endif
                </div></div>
            @endif
        @endcan
    </div>

    {{-- Right rail --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Asset</h3></div>
            <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                <div class="flex justify-between gap-3 px-5 py-2.5"><dt class="text-slate-500 dark:text-slate-400">Content type</dt><dd class="text-right font-medium text-slate-700 dark:text-slate-200">{{ ucfirst(str_replace('_', ' ', $asset->content_type)) }}</dd></div>
                <div class="flex justify-between gap-3 px-5 py-2.5"><dt class="text-slate-500 dark:text-slate-400">Uploaded</dt><dd class="text-right font-medium text-slate-700 dark:text-slate-200">{{ $asset->created_at?->format('j M Y H:i') }}</dd></div>
                @if ($job)
                    <div class="flex justify-between gap-3 px-5 py-2.5"><dt class="text-slate-500 dark:text-slate-400">Analysis completed</dt><dd class="text-right font-medium text-slate-700 dark:text-slate-200">{{ $job->completed_at?->diffForHumans() ?? '—' }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
</div>
@endsection
