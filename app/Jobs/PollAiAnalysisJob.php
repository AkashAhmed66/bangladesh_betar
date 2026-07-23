<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiAnalysisJob;
use App\Models\AiSuggestion;
use App\Models\AuditLog;
use App\Models\AudioAsset;
use App\Models\Language;
use App\Models\Transcript;
use App\Services\AiAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * M16 — polls GET /jobs/{id} on the external audio-postmortem service every
 * ~10s (self-requeuing) until it reports "done" or "error", then applies the
 * result: saves the transcript, and routes the asset either back into the
 * normal pipeline (draft) or into `ai_flagged` for AI Reviewer sign-off when
 * duplicate / violence / anti-government content was detected.
 */
class PollAiAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Retries are handled by re-dispatching ourselves with a delay, not by
    // the queue worker's own retry mechanism.
    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(private readonly int $aiAnalysisJobId) {}

    public function handle(AiAnalysisService $ai): void
    {
        $job = AiAnalysisJob::query()->with('audioAsset')->find($this->aiAnalysisJobId);
        $asset = $job?->audioAsset;

        // Row deleted, asset deleted, or already resolved by an earlier
        // (possibly overlapping) run — nothing left to do.
        if (! $job || ! $asset || $job->status !== 'processing') {
            return;
        }

        try {
            $result = $ai->poll((string) $job->job_id);
        } catch (\Throwable $e) {
            $this->reschedule($job, $asset, $e->getMessage());

            return;
        }

        $status = $result['status'] ?? 'processing';

        if ($status === 'processing') {
            $this->reschedule($job, $asset, null);

            return;
        }

        if ($status === 'error') {
            $this->finishWithError($job, $asset, (string) ($result['error'] ?? 'The analysis service reported an error.'));

            return;
        }

        $this->finishWithResult($job, $asset, $result);
    }

    public function failed(\Throwable $e): void
    {
        $job = AiAnalysisJob::query()->with('audioAsset')->find($this->aiAnalysisJobId);
        if ($job && $job->audioAsset && $job->status === 'processing') {
            $this->finishWithError($job, $job->audioAsset, $e->getMessage());
        }
    }

    /** Still processing (or a transient poll failure) — try again shortly, up to a cap. */
    private function reschedule(AiAnalysisJob $job, AudioAsset $asset, ?string $transientError): void
    {
        $maxAttempts = (int) config('services.audio_postmortem.max_poll_attempts', 60);
        $intervalSeconds = (int) config('services.audio_postmortem.poll_interval_seconds', 10);

        $job->increment('attempts');
        $job->update(['polled_at' => now()]);

        if ($job->attempts >= $maxAttempts) {
            $this->finishWithError($job, $asset, $transientError ?? 'Timed out waiting for the AI analysis service.');

            return;
        }

        self::dispatch($this->aiAnalysisJobId)->delay(now()->addSeconds($intervalSeconds));
    }

    private function finishWithResult(AiAnalysisJob $job, AudioAsset $asset, array $result): void
    {
        $duplicate = (array) ($result['duplicate'] ?? []);
        $matched = (array) ($duplicate['matched_against'] ?? []);
        $analysis = (array) ($result['analysis'] ?? []);
        $violence = (array) ($analysis['violence'] ?? []);
        $antiGov = (array) ($analysis['anti_government'] ?? []);

        $job->update([
            'status' => 'done',
            'completed_at' => now(),
            'polled_at' => now(),
            'language' => $result['language'] ?? null,
            'duration_sec' => $result['duration_sec'] ?? null,
            'transcript' => $result['transcript'] ?? null,
            'transcript_readable' => $result['transcript_readable'] ?? null,
            'has_audio_fingerprint' => (bool) ($result['has_audio_fingerprint'] ?? false),
            'is_duplicate' => (bool) ($duplicate['is_duplicate'] ?? false),
            'audio_match_pct' => $duplicate['audio_match_pct'] ?? null,
            'content_match_pct' => $duplicate['content_match_pct'] ?? null,
            'matched_job_id' => $matched['job_id'] ?? null,
            'matched_filename' => $matched['filename'] ?? null,
            'matched_uploaded_at' => $matched['uploaded_at'] ?? null,
            'violence_detected' => (bool) ($violence['detected'] ?? false),
            'violence_confidence' => $violence['confidence'] ?? null,
            'violence_evidence' => $violence['evidence'] ?? null,
            'anti_government_detected' => (bool) ($antiGov['detected'] ?? false),
            'anti_government_confidence' => $antiGov['confidence'] ?? null,
            'anti_government_evidence' => $antiGov['evidence'] ?? null,
            'summary' => $analysis['summary'] ?? null,
            'raw_response' => $result,
        ]);
        $job->refresh();

        // Show the transcription regardless of the moderation outcome.
        $transcriptText = $job->transcript_readable ?: $job->transcript;
        if ($transcriptText) {
            Transcript::query()->updateOrCreate(
                ['audio_asset_id' => $asset->id, 'transcript_type' => 'transcript'],
                [
                    'language_id' => $this->resolveLanguageId($job->language),
                    'full_text' => $transcriptText,
                    'is_ai_generated' => true,
                    'is_verified' => false,
                ],
            );
        }

        if ($job->isFlagged()) {
            $job->update(['review_status' => 'pending']);
            $asset->update(['status' => 'ai_flagged']);

            AiSuggestion::query()->create([
                'audio_asset_id' => $asset->id,
                'suggestion_type' => 'content_moderation',
                'payload' => [
                    'ai_analysis_job_id' => $job->id,
                    'is_duplicate' => $job->is_duplicate,
                    'violence_detected' => $job->violence_detected,
                    'anti_government_detected' => $job->anti_government_detected,
                    'summary' => $job->summary,
                ],
                'status' => 'pending',
            ]);

            AuditLog::record('ai_analysis_flagged', $asset, null, [
                'ai_analysis_job' => $job->id,
                'duplicate' => $job->is_duplicate,
                'violence' => $job->violence_detected,
                'anti_government' => $job->anti_government_detected,
            ], "AI analysis flagged {$asset->archive_no} — awaiting AI Reviewer sign-off.");
        } else {
            $job->update(['review_status' => 'not_required']);
            $asset->update(['status' => 'draft']);

            AuditLog::record('ai_analysis_cleared', $asset, null, [
                'ai_analysis_job' => $job->id,
            ], "AI analysis cleared {$asset->archive_no} — no duplicate/violence/anti-government content detected.");
        }
    }

    private function finishWithError(AiAnalysisJob $job, AudioAsset $asset, string $message): void
    {
        $job->update(['status' => 'error', 'error' => $message, 'completed_at' => now()]);

        // Don't leave the asset stuck in "analyzing" forever — unblock it so
        // staff can still proceed through the ordinary manual pipeline.
        if ($asset->status === 'analyzing') {
            $asset->update(['status' => 'draft']);
        }

        AuditLog::record('ai_analysis_error', $asset, null, [
            'ai_analysis_job' => $job->id, 'error' => $message,
        ], "AI analysis failed for {$asset->archive_no}: {$message}");
    }

    /** Map the service's `language` (e.g. "bengali") onto a seeded Language row. */
    private function resolveLanguageId(?string $language): ?int
    {
        if (! $language) {
            return null;
        }

        $code = match (strtolower($language)) {
            'bengali', 'bangla', 'bn' => 'bn',
            'english', 'en' => 'en',
            default => null,
        };

        return $code ? Language::query()->where('code', $code)->value('id') : null;
    }
}
