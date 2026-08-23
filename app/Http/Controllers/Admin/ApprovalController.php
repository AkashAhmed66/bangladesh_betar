<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\ApprovalAction;
use App\Models\AudioAsset;
use App\Models\AudioBook;
use App\Models\NewsArticle;
use App\Models\RightsRecord;
use App\Models\WatchShow;
use App\Services\EditorialApprovalService;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M13 — approval queue and multi-stage review actions (FR-WRK-03/05).
 */
class ApprovalController extends Controller
{
    public function __construct(private readonly EditorialApprovalService $editorialApprovals) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        // Tabs:
        //   "queue"     — My Queue: MY submissions awaiting others' approval,
        //                 with current stage + the role that must act; completed
        //                 items stay listed as history.
        //   "approvals" — My Approvals: records that need MY approval (items at
        //                 stages my roles own; their completed history stays).
        //   "all"       — full register, records.view-all holders only.
        $scope = in_array($request->query('scope'), ['approvals', 'all'], true)
            ? (string) $request->query('scope')
            : 'queue';

        if ($scope === 'all' && ! $user->can('records.view-all')) {
            $scope = 'queue';
        }

        $approvals = Approval::query()
            ->whereNotIn('approvable_type', ['news_article', 'watch_show'])
            ->when($scope === 'queue', fn ($q) => $q->where('submitted_by', $user->id))
            ->when($scope === 'approvals' && ! $user->hasRole('Super Administrator'), fn ($q) => $q->whereHas(
                'currentStage',
                fn ($s) => $s->whereIn('approver_role', $user->getRoleNames()->all()),
            ))
            ->with([
                // Rights records ride along so the queue can show the
                // post-approval pipeline (rights submission → cleared → publish).
                'approvable' => fn ($morphTo) => $morphTo->morphWith([AudioAsset::class => ['rightsRecords']]),
                'workflow', 'currentStage', 'submitter',
            ])
            // In-flight first (FIFO), completed history after.
            ->orderByRaw("(status in ('pending', 'changes_requested')) desc")
            ->orderBy('submitted_at')
            ->paginate(15)
            ->withQueryString();

        // ---- AI moderation + rights submissions ride along in the feed -----
        // Every review gate shows in one place: reviewers see what needs THEM
        // (My Approvals), submitters see THEIR items (My Queue).
        $aiBase = AudioAsset::query()->with(['latestAiAnalysisJob', 'uploader'])
            ->whereHas('aiAnalysisJobs', fn ($q) => $q->where('review_status', 'pending'));
        $aiItems = match (true) {
            $scope === 'queue' => (clone $aiBase)->where('uploaded_by', $user->id)->latest('updated_at')->take(20)->get(),
            $scope === 'approvals' && $user->can('ai-moderation.review') => (clone $aiBase)->latest('updated_at')->take(20)->get(),
            $scope === 'all' => (clone $aiBase)->latest('updated_at')->take(20)->get(),
            default => collect(),
        };

        $rightsBase = RightsRecord::query()->with(['audioAsset.uploader', 'rightsHolder', 'creator'])
            ->where('status', 'pending');
        $rightsItems = match (true) {
            $scope === 'queue' => (clone $rightsBase)->where(fn ($q) => $q->where('created_by', $user->id)
                ->orWhereHas('audioAsset', fn ($a) => $a->where('uploaded_by', $user->id)))->latest()->take(20)->get(),
            $scope === 'approvals' && $user->can('rights.manage') => (clone $rightsBase)->latest()->take(20)->get(),
            $scope === 'all' => (clone $rightsBase)->latest()->take(20)->get(),
            default => collect(),
        };

        $bookBase = AudioBook::query()->with('user')->where('status', 'pending_approval');
        $bookItems = match (true) {
            $scope === 'queue' => (clone $bookBase)->where('user_id', $user->id)->latest('submitted_at')->take(20)->get(),
            $scope === 'approvals' && $user->can('audiobooks.approve') => (clone $bookBase)->latest('submitted_at')->take(20)->get(),
            $scope === 'all' => (clone $bookBase)->latest('submitted_at')->take(20)->get(),
            default => collect(),
        };

        $editorialItems = function (string $type) use ($scope, $user) {
            return Approval::query()
                ->where('approvable_type', $type)
                ->when($scope === 'queue', fn (Builder $query) => $query->where('submitted_by', $user->id))
                ->when(
                    $scope === 'approvals' && ! $user->hasRole('Super Administrator'),
                    fn (Builder $query) => $query->whereHas(
                        'currentStage',
                        fn (Builder $stage) => $stage->whereIn('approver_role', $user->getRoleNames()->all()),
                    ),
                )
                ->with(['approvable', 'workflow', 'currentStage', 'submitter'])
                ->orderByRaw("(status in ('pending', 'changes_requested')) desc")
                ->orderByDesc('submitted_at')
                ->take(50)
                ->get();
        };

        $newsItems = $editorialItems('news_article');
        $watchItems = $editorialItems('watch_show');

        return view('admin.approvals.index', compact('approvals', 'scope', 'aiItems', 'rightsItems', 'bookItems', 'newsItems', 'watchItems'));
    }

    public function show(Approval $approval): View
    {
        $user = auth()->user();

        // Visible to: view-all holders, the submitter, and approvers whose
        // role owns the item's (current or final) stage — completed included,
        // so the history stays reviewable.
        abort_unless(
            $user->can('records.view-all')
                || $approval->submitted_by === $user->id
                || ($user->can('approvals.act') && $approval->currentStage && $user->hasRole($approval->currentStage->approver_role)),
            403,
            'You can only view approvals you submitted.',
        );

        $approval->load(['approvable', 'workflow.stages', 'currentStage', 'submitter', 'actions.user']);

        // The review page shows the FULL asset record inline (player, metadata,
        // AI analysis, transcripts, rights, versions) so approvers have every
        // detail on the page where they decide.
        if ($approval->approvable instanceof AudioAsset) {
            $approval->approvable->load([
                'versions' => fn ($q) => $q->orderByDesc('is_default')->orderBy('id'),
                'station', 'department', 'programme', 'category', 'language', 'uploader',
                'tags', 'artists', 'transcripts', 'rightsRecords.rightsHolder',
                'latestAiAnalysisJob.reviewer',
            ]);
        }
        if ($approval->approvable instanceof WatchShow) {
            $approval->approvable->load('episodes');
        }
        if ($approval->approvable instanceof NewsArticle) {
            $approval->approvable->load('media');
        }

        return view('admin.approvals.show', compact('approval'));
    }

    public function act(Request $request, Approval $approval): RedirectResponse
    {
        $approval->loadMissing(['currentStage', 'workflow', 'submitter', 'approvable']);
        abort_unless($approval->isActionableBy($request->user()), 403, 'This approval is not assigned to your role.');

        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'request_changes'])],
            'comments' => ['nullable', 'string', 'required_if:action,reject', 'required_if:action,request_changes'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        if (! in_array($approval->status, ['pending', 'changes_requested'], true)) {
            return back()->with('error', 'This approval has already been completed.');
        }

        if (($approval->approvable instanceof NewsArticle || $approval->approvable instanceof WatchShow)
            && $approval->submitted_by === $request->user()->id
            && ! $request->user()->hasRole('Super Administrator')) {
            return back()->with('error', 'Editorial content must be approved by a different authorised reviewer.');
        }

        $stage = $approval->currentStage;
        $asset = $approval->approvable instanceof AudioAsset ? $approval->approvable : null;

        ApprovalAction::query()->create([
            'approval_id' => $approval->id,
            'workflow_stage_id' => $stage?->id,
            'user_id' => $request->user()->id,
            'action' => match ($data['action']) {
                'approve' => 'approved',
                'reject' => 'rejected',
                'request_changes' => 'correction_requested',
            },
            'comments' => $data['comments'] ?? null,
            'rating' => $data['rating'] ?? null,
        ]);

        $actor = $request->user();
        $title = $asset?->title ?? 'Item #'.$approval->approvable_id;
        if ($approval->approvable instanceof NewsArticle || $approval->approvable instanceof WatchShow) {
            $title = $approval->approvable->title;
        }
        $showUrl = route('admin.approvals.show', $approval);
        $contentUrl = $asset
            ? route('admin.assets.show', $asset)
            : $this->editorialApprovals->reviewUrl($approval->approvable);

        if ($data['action'] === 'approve') {
            $next = $stage?->next();

            if ($next) {
                $approval->update(['current_stage_id' => $next->id]);
                $this->editorialApprovals->applyDecision($approval, 'approve', false);
                $message = "Approved. Advanced to stage: {$next->name}.";

                // Stage change: tell the submitter where it stands, and the
                // next stage's approvers that it now needs them.
                Notify::user($approval->submitter?->is($actor) ? null : $approval->submitter, 'stage_advanced',
                    'Approval advanced a stage',
                    "“{$title}” passed “{$stage->name}” and moved to “{$next->name}” (needs {$next->approver_role}).",
                    $showUrl);
                Notify::role($next->approver_role, 'needs_approval',
                    'Needs your approval',
                    "“{$title}” reached stage “{$next->name}” — submitted by ".($approval->submitter?->name ?? '—').'.',
                    $showUrl,
                    except: $actor->id);
            } else {
                $approval->update(['status' => 'approved', 'completed_at' => now()]);
                $asset?->update(['status' => 'approved']);
                $this->editorialApprovals->applyDecision($approval, 'approve', true);
                $message = 'Approved. Workflow complete.';

                $completionMessage = $asset
                    ? "\"{$title}\" is fully approved. Next: submit the copyright documents from the asset record."
                    : "\"{$title}\" is fully approved and ready for an authorised publisher to publish.";
                Notify::user($approval->submitter?->is($actor) ? null : $approval->submitter, 'approved',
                    'Approval complete',
                    $completionMessage,
                    $contentUrl);
            }
        } elseif ($data['action'] === 'reject') {
            $approval->update(['status' => 'rejected', 'completed_at' => now()]);
            $asset?->update(['status' => 'rejected']);
            $this->editorialApprovals->applyDecision($approval, 'reject', true);
            $message = 'Approval rejected.';

            Notify::user($approval->submitter?->is($actor) ? null : $approval->submitter, 'rejected',
                'Submission rejected',
                "“{$title}” was rejected at stage “{$stage?->name}”".($data['comments'] ? ': '.$data['comments'] : '.'),
                $contentUrl);
        } else {
            $approval->update(['status' => 'changes_requested']);
            $asset?->update(['status' => 'draft']);
            $this->editorialApprovals->applyDecision($approval, 'request_changes', false);
            $message = 'Changes requested from the submitter.';

            Notify::user($approval->submitter?->is($actor) ? null : $approval->submitter, 'changes_requested',
                'Changes requested',
                "“{$title}”: ".($data['comments'] ?: 'changes were requested')." — update it and resubmit for approval.",
                $contentUrl);
        }

        return back()->with('success', $message);
    }
}
