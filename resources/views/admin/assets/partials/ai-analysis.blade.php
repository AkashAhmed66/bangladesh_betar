@if ($pending)
    <div class="card" role="status" aria-live="polite">
        <div class="card-body flex items-center gap-3">
            <svg class="size-5 shrink-0 animate-spin text-primary-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Running AI safety analysis...</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Checking for duplicates, safety issues, and generating the transcript. The results will appear here automatically.</p>
            </div>
        </div>
    </div>
@elseif ($job)
    <div class="card" aria-live="polite">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">AI Safety Analysis</h3>
            @if ($job->status === 'error')
                <span class="badge-red">Analysis failed</span>
            @elseif ($job->isFlagged())
                <span class="badge-amber">Flagged for review</span>
            @else
                <span class="badge-green">Cleared</span>
            @endif
        </div>
        <div class="card-body space-y-3">
            @if ($job->status === 'error')
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $job->error ?? 'The analysis service could not be reached.' }} The asset is awaiting manual AI moderation review.</p>
            @else
                <div class="flex flex-wrap gap-1.5">
                    @if ($job->is_duplicate)<span class="badge-amber">Duplicate</span>@endif
                    @if ($job->violence_detected)<span class="badge-red">Violence</span>@endif
                    @if ($job->anti_government_detected)<span class="badge-red">Anti-government</span>@endif
                    @if (! $job->isFlagged())<span class="badge-slate">No issues found</span>@endif
                </div>
                @if ($job->summary)<p class="text-sm text-slate-600 dark:text-slate-300">{{ $job->summary }}</p>@endif
            @endif

            @if (in_array($asset->status, ['ai_review', 'ai_flagged'], true))
                @can('ai-moderation.view')
                    <a href="{{ route('admin.ai-moderation.show', $asset) }}" class="btn-primary btn-sm"><x-icon name="shield" class="size-4" /> Review in AI Moderation</a>
                @endcan
            @elseif ($asset->status === 'ai_rejected')
                <p class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                    Rejected by AI Reviewer{{ $job->reviewer ? ' '.$job->reviewer->name : '' }}{{ $job->reviewed_at ? ' - '.$job->reviewed_at->diffForHumans() : '' }}; this asset cannot be submitted for approval or published.
                    @if ($job->review_comments) "{{ $job->review_comments }}" @endif
                </p>
            @endif
        </div>
    </div>
@endif

@if ($asset->transcripts->isNotEmpty())
    <div class="card mt-6" aria-live="polite">
        <div class="card-header"><h3 class="font-semibold text-slate-800 dark:text-slate-100">Transcripts & Lyrics</h3></div>
        <div class="card-body space-y-4">
            @foreach ($asset->transcripts as $transcript)
                <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <span class="badge-slate">{{ ucfirst($transcript->transcript_type) }}</span>
                        @if ($transcript->is_ai_generated)<span class="badge-purple">AI generated</span>@endif
                        @if ($transcript->is_verified)
                            <span class="badge-green">Verified</span>
                        @else
                            <span class="badge-amber">Unverified draft (FR-AIF-06)</span>
                        @endif
                    </div>
                    <div class="space-y-1.5 text-sm text-slate-600 dark:text-slate-300">
                        @foreach (array_slice($transcript->lines ?? [], 0, 4) as $line)
                            <p><span class="mr-2 tabular-nums text-xs text-slate-400">{{ gmdate('i:s', (int) $line['start']) }}</span>
                            <span class="mr-1 text-xs font-medium text-primary-700 dark:text-primary-300">{{ $line['speaker'] ?? '' }}:</span>{{ $line['text'] }}</p>
                        @endforeach
                        @if (empty($transcript->lines) && $transcript->full_text)
                            <p class="font-bangla whitespace-pre-wrap leading-relaxed">{{ Str::limit($transcript->full_text, 600) }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
