<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Approval;
use App\Models\ApprovalAction;
use App\Models\NewsArticle;
use App\Models\User;
use App\Models\WatchShow;
use App\Models\Workflow;
use App\Support\Notify;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class EditorialApprovalService
{
    public function submit(NewsArticle|WatchShow $content, User $submitter, string $workflowType, ?string $comments = null): Approval
    {
        if ($content->approvals()->pending()->exists()) {
            throw new DomainException('This item already has an active approval request.');
        }

        $workflow = Workflow::forContentType($workflowType);
        if (! $workflow) {
            throw new DomainException('No active approval workflow is configured for this content type.');
        }

        $stage = $workflow->stages()->first();
        if (! $stage) {
            throw new DomainException('The approval workflow has no review stages.');
        }

        $approval = DB::transaction(function () use ($content, $submitter, $workflow, $stage, $comments): Approval {
            $approval = Approval::query()->create([
                'approvable_type' => $content->getMorphClass(),
                'approvable_id' => $content->getKey(),
                'workflow_id' => $workflow->id,
                'current_stage_id' => $stage->id,
                'status' => 'pending',
                'submitted_by' => $submitter->id,
                'submitted_at' => now(),
            ]);

            ApprovalAction::query()->create([
                'approval_id' => $approval->id,
                'workflow_stage_id' => $stage->id,
                'user_id' => $submitter->id,
                'action' => 'submitted',
                'comments' => $comments ?: 'Submitted for editorial approval.',
            ]);

            $content->update([
                'approval_status' => 'pending',
                'is_published' => false,
            ]);

            return $approval;
        });

        $reviewers = User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                $stage->approver_role,
                'Super Administrator',
            ]))
            ->get();
        Notify::users(
            $reviewers,
            'needs_approval',
            'Editorial content needs your approval',
            "{$submitter->name} submitted \"{$content->title}\" for {$stage->name}.",
            route('admin.approvals.show', $approval),
            except: $submitter->id,
        );

        return $approval;
    }

    public function invalidate(NewsArticle|WatchShow $content, User $editor): void
    {
        DB::transaction(function () use ($content, $editor): void {
            $content->approvals()->pending()->with('currentStage')->get()->each(function (Approval $approval) use ($editor): void {
                $approval->update(['status' => 'cancelled', 'completed_at' => now()]);
                ApprovalAction::query()->create([
                    'approval_id' => $approval->id,
                    'workflow_stage_id' => $approval->current_stage_id,
                    'user_id' => $editor->id,
                    'action' => 'cancelled',
                    'comments' => 'Approval invalidated because the content was edited.',
                ]);
            });

            $content->update([
                'approval_status' => 'draft',
                'is_published' => false,
                'published_at' => null,
            ]);
        });
    }

    public function applyDecision(Approval $approval, string $decision, bool $workflowComplete): void
    {
        $content = $approval->approvable;
        if (! $content instanceof NewsArticle && ! $content instanceof WatchShow) {
            return;
        }

        $approvalStatus = match ($decision) {
            'approve' => $workflowComplete ? 'approved' : 'pending',
            'reject' => 'rejected',
            'request_changes' => 'changes_requested',
            default => throw new DomainException('Unknown editorial approval decision.'),
        };

        $content->update([
            'approval_status' => $approvalStatus,
            'is_published' => false,
        ]);
    }

    public function reviewUrl(Model $content): string
    {
        return match (true) {
            $content instanceof NewsArticle => route('admin.news-articles.edit', $content),
            $content instanceof WatchShow => route('admin.watch-shows.edit', $content),
            default => route('admin.approvals.index'),
        };
    }
}
