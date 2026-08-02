<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysisJob;
use App\Models\AudioAsset;
use App\Models\AuditLog;
use App\Models\Transcript;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M16 — AI Reviewer queue. EVERY upload passes through here: the analysis
 * result (clean, flagged, or failed) holds the asset in `ai_review` /
 * `ai_flagged` until a member of the AI Reviewer role approves or rejects
 * it. Only then does it continue into cataloguing → approval → rights →
 * publish. A reject is terminal: the asset can never be submitted for
 * approval or published (FR-WRK-06 style gate, enforced again server-side
 * in AudioAssetController regardless of what the UI offers).
 */
class AiModerationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');   // pending | approved | rejected
        $flag = (string) $request->query('flag', '');       // duplicate | violence | anti_government

        // The moderation queue is every upload that required sign-off — pending
        // AND already-decided — so rows persist after accept/reject.
        $assets = AudioAsset::query()
            ->with(['latestAiAnalysisJob.reviewer', 'uploader'])
            ->whereHas('aiAnalysisJobs', fn ($q) => $q->whereIn('review_status', ['pending', 'approved', 'rejected']))
            ->when($search !== '', fn ($qq) => $qq->where(function ($w) use ($search): void {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('archive_no', 'like', "%{$search}%")
                    ->orWhereHas('uploader', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true),
                fn ($qq) => $qq->whereHas('aiAnalysisJobs', fn ($q) => $q->where('review_status', $status)))
            ->when(in_array($flag, ['duplicate', 'violence', 'anti_government'], true),
                fn ($qq) => $qq->whereHas('aiAnalysisJobs', fn ($q) => $q->where(match ($flag) {
                    'duplicate' => 'is_duplicate',
                    'violence' => 'violence_detected',
                    'anti_government' => 'anti_government_detected',
                }, true)))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.ai-moderation.index', compact('assets', 'search', 'status', 'flag'));
    }

    public function show(AudioAsset $asset): View
    {
        abort_unless(
            $asset->aiAnalysisJobs()->whereIn('review_status', ['pending', 'approved', 'rejected'])->exists(),
            404,
            'This asset has no AI moderation record.',
        );

        $asset->load(['uploader', 'transcripts', 'aiAnalysisJobs.reviewer']);
        $job = $asset->aiAnalysisJobs->first(); // latest() ordering from the relation

        return view('admin.ai-moderation.show', compact('asset', 'job'));
    }

    public function review(Request $request, AudioAsset $asset): RedirectResponse
    {
        $this->authorize('ai-moderation.review');

        abort_unless(in_array($asset->status, ['ai_flagged', 'ai_review'], true), 404, 'This asset is not awaiting AI review.');

        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            // A remark is mandatory for BOTH accept and reject so every decision
            // carries an auditable justification.
            'comments' => ['required', 'string', 'max:2000'],
        ], [
            'comments.required' => 'A remark is required to record this decision.',
        ]);

        /** @var AiAnalysisJob|null $job */
        $job = $asset->aiAnalysisJobs()->pendingReview()->first();

        $approved = $data['action'] === 'approve';

        // Capture the before-state so the audit trail shows exactly what the
        // reviewer changed (FR-AUD-02).
        $before = [
            'asset_status' => $asset->status,
            'review_status' => $job?->review_status,
        ];

        $job?->update([
            'review_status' => $approved ? 'approved' : 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_comments' => $data['comments'] ?? null,
        ]);

        // Approved: back into the ordinary manual pipeline. Rejected: terminal —
        // never reachable by submitForApproval() or publish() again.
        $asset->update(['status' => $approved ? 'draft' : 'ai_rejected']);

        AuditLog::record(
            $approved ? 'ai_review_approved' : 'ai_review_rejected',
            $asset,
            $before,
            [
                'asset_status' => $asset->status,
                'review_status' => $approved ? 'approved' : 'rejected',
                'remarks' => $data['comments'],
                'ai_analysis_job' => $job?->id,
            ],
            "AI Reviewer ".($approved ? 'cleared' : 'rejected')." {$asset->archive_no}.",
        );

        // Tell the uploader the outcome of the AI-moderation gate.
        if ($asset->uploader && $asset->uploaded_by !== $request->user()->id) {
            Notify::user($asset->uploader, $approved ? 'ai_approved' : 'ai_rejected',
                $approved ? 'AI moderation cleared' : 'Rejected at AI moderation',
                $approved
                    ? "“{$asset->title}” passed AI moderation — you can now submit it for approval."
                    : "“{$asset->title}” was rejected at AI moderation: {$data['comments']}",
                route('admin.assets.show', $asset));
        }

        return redirect()->route('admin.ai-moderation.index')->with(
            'success',
            $approved
                ? "{$asset->archive_no} cleared — it can now continue through cataloguing and approval."
                : "{$asset->archive_no} rejected. It can never be submitted for approval or published.",
        );
    }

    /**
     * FR-AIF-06 — correct the AI-generated transcript during moderation. The
     * edit is stored as the asset's verified transcript (shown everywhere the
     * transcript is used, including public search).
     */
    public function updateTranscript(Request $request, AudioAsset $asset): RedirectResponse
    {
        $this->authorize('ai-moderation.review');

        abort_unless(
            $asset->aiAnalysisJobs()->whereIn('review_status', ['pending', 'approved', 'rejected'])->exists(),
            404,
            'This asset has no AI moderation record.',
        );

        $data = $request->validate([
            'full_text' => ['required', 'string', 'max:200000'],
        ]);

        // Before-state: the current transcript text (or the raw AI output when
        // no Transcript row exists yet) — kept verbatim in the audit trail so
        // every moderator correction is fully reconstructable (FR-AUD-02).
        $existing = Transcript::query()
            ->where('audio_asset_id', $asset->id)->where('transcript_type', 'transcript')->first();
        $latestJob = $asset->latestAiAnalysisJob;
        $oldText = $existing?->full_text ?? ($latestJob?->transcript_readable ?: $latestJob?->transcript);

        if ($oldText === $data['full_text']) {
            return back()->with('success', 'Transcript unchanged.');
        }

        Transcript::query()->updateOrCreate(
            ['audio_asset_id' => $asset->id, 'transcript_type' => 'transcript'],
            [
                'full_text' => $data['full_text'],
                'is_ai_generated' => true,
                'is_verified' => true,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ],
        );

        AuditLog::record('ai_transcript_edited', $asset,
            ['full_text' => $oldText, 'is_verified' => (bool) $existing?->is_verified],
            ['full_text' => $data['full_text'], 'is_verified' => true],
            sprintf('Transcript for %s corrected during AI moderation (%s → %s characters).',
                $asset->archive_no, number_format(mb_strlen((string) $oldText)), number_format(mb_strlen($data['full_text']))));

        return back()->with('success', 'Transcript updated and marked verified.');
    }
}
