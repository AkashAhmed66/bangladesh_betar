<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'current_stage_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class)->latest();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'changes_requested']);
    }

    /** Pending approvals actionable by the given user's roles (FR-WRK-05). */
    public function scopeActionableBy(Builder $query, User $user): Builder
    {
        $roleNames = $user->getRoleNames()->all();

        return $query->where('status', 'pending')
            ->whereHas('currentStage', fn (Builder $s) => $s->whereIn('approver_role', $roleNames));
    }

    /** Whether this user can act on it right now (status + stage role). */
    public function isActionableBy(User $user): bool
    {
        return $user->can('approvals.act')
            && in_array($this->status, ['pending', 'changes_requested'], true)
            && $this->currentStage !== null
            && $user->hasRole($this->currentStage->approver_role);
    }
}
