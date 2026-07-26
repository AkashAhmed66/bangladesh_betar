<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAnalysisJob;
use App\Models\AudioAsset;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M16 — AI Reviewer queue. Assets the audio-postmortem service flagged as a
 * possible duplicate, or containing violent / anti-government content, sit
 * in `ai_flagged` until a member of the AI Reviewer role clears or rejects
 * them here. A reject is terminal: the asset can never be submitted for
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

        // The moderation queue is every asset the AI ever flagged — pending AND
        // already-decided — so rows persist after accept/reject with their status.
        $assets = AudioAsset::query()
            ->with(['latestAiAnalysisJob.reviewer', 'uploader'])
            ->whereHas('aiAnalysisJobs', fn ($q) => $q->flagged())
            ->when($search !== '', fn ($qq) => $qq->where(function ($w) use ($search): void {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('archive_no', 'like', "%{$search}%")
                    ->orWhereHas('uploader', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            }))
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true),
                fn ($qq) => $qq->whereHas('aiAnalysisJobs', fn ($q) => $q->flagged()->where('review_status', $status)))
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
            $asset->aiAnalysisJobs()->flagged()->exists(),
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

        abort_unless($asset->status === 'ai_flagged', 404, 'This asset is not awaiting AI review.');

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
            null,
            ['ai_analysis_job' => $job?->id, 'comments' => $data['comments'] ?? null],
            "AI Reviewer ".($approved ? 'cleared' : 'rejected')." {$asset->archive_no}.",
        );

        return redirect()->route('admin.ai-moderation.index')->with(
            'success',
            $approved
                ? "{$asset->archive_no} cleared — it can now continue through cataloguing and approval."
                : "{$asset->archive_no} rejected. It can never be submitted for approval or published.",
        );
    }
}
