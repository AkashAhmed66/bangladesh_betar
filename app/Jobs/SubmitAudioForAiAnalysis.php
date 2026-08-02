<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiAnalysisJob;
use App\Models\AuditLog;
use App\Models\AudioAsset;
use App\Services\AiAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * M16 — kicks off the external audio-postmortem analysis (duplicate /
 * violence / anti-government detection + transcription) for a newly
 * ingested asset. Dispatched right after upload so the HTTP response to the
 * uploader returns instantly; the asset sits in `analyzing` status while
 * this — and the {@see PollAiAnalysisJob} it schedules — run in the
 * background.
 */
class SubmitAudioForAiAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(private readonly int $audioAssetId)
    {
        // Always run on the database queue so the upload request returns
        // instantly and the AI analysis (a slow external call) happens in the
        // background — even when the environment's default QUEUE_CONNECTION is
        // 'sync', which would otherwise run this inline and block/500 the
        // upload. The supervisor worker processes this same queue.
        $this->onConnection('database');
    }

    public function handle(AiAnalysisService $ai): void
    {
        $asset = AudioAsset::query()->with('masterVersion')->find($this->audioAssetId);
        if (! $asset) {
            return;
        }

        $disk = Storage::disk('local');
        $master = $asset->masterVersion;
        $absolutePath = $master && $master->file_path && $disk->exists($master->file_path)
            ? $disk->path($master->file_path)
            : null;

        if ($absolutePath === null) {
            $this->holdForReview($asset, 'No audio file available to analyze.');

            return;
        }

        $job = AiAnalysisJob::query()->create([
            'audio_asset_id' => $asset->id,
            'source_api' => 'analyze',
            'status' => 'processing',
        ]);

        try {
            $externalJobId = $ai->submit($absolutePath);
        } catch (\Throwable $e) {
            $job->update(['status' => 'error', 'error' => $e->getMessage(), 'completed_at' => now(), 'review_status' => 'pending']);
            $this->holdForReview($asset, 'AI analysis could not be started: '.$e->getMessage(), createJob: false);

            return;
        }

        $job->update(['job_id' => $externalJobId]);

        PollAiAnalysisJob::dispatch($job->id)
            ->delay(now()->addSeconds((int) config('services.audio_postmortem.poll_interval_seconds', 10)));
    }

    public function failed(\Throwable $e): void
    {
        $asset = AudioAsset::query()->find($this->audioAssetId);
        if ($asset) {
            $this->holdForReview($asset, $e->getMessage());
        }
    }

    /**
     * Analysis could not run — the AI-moderation gate still applies. Hold the
     * asset in `ai_review` (with an error job so it appears in the queue) for
     * a human reviewer to sign off manually.
     */
    private function holdForReview(AudioAsset $asset, string $reason, bool $createJob = true): void
    {
        if ($createJob) {
            AiAnalysisJob::query()->create([
                'audio_asset_id' => $asset->id,
                'source_api' => 'analyze',
                'status' => 'error',
                'error' => $reason,
                'completed_at' => now(),
                'review_status' => 'pending',
            ]);
        }

        if ($asset->status === 'analyzing') {
            $asset->update(['status' => 'ai_review']);
        }

        AuditLog::record('ai_analysis_error', $asset, null, ['reason' => $reason], "AI analysis unavailable for {$asset->archive_no}: {$reason} — held for manual AI-moderation review.");

        \App\Support\Notify::permission('ai-moderation.review', 'ai_pending',
            'Upload needs manual AI review',
            "Analysis could not run for “{$asset->title}” — review it manually before it can proceed.",
            route('admin.ai-moderation.show', $asset));
    }
}
