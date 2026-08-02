<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Record-level visibility: users holding the `records.view-all` permission see
 * every record in the modules they can access; everyone else sees only the
 * records they created (plus, per model, records awaiting their action).
 *
 * Modules with a review/assignment flow (approvals, rights, AI moderation) and
 * shared infrastructure (stations, taxonomies, workflows) intentionally do NOT
 * use this trait — permitted users always see all records there.
 */
trait HasRecordVisibility
{
    /** Stamp the creating user so scoping has something to key on. */
    public static function bootHasRecordVisibility(): void
    {
        static::creating(function ($model): void {
            $column = static::creatorColumn();

            if ($model->getAttribute($column) === null) {
                $model->setAttribute($column, auth()->id());
            }
        });
    }

    /** Column that records the creating user. Override when it differs. */
    public static function creatorColumn(): string
    {
        return 'created_by';
    }

    /** Limit a listing to what the user may see (no-op for `records.view-all`). */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('records.view-all')) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $this->applyVisibility($q, $user));
    }

    /**
     * The restricted-visibility condition: own records only. Models may
     * override to add "needs my action" clauses (kept grouped by the caller,
     * so orWhere is safe here).
     */
    protected function applyVisibility(Builder $query, User $user): void
    {
        $query->where($this->qualifyColumn(static::creatorColumn()), $user->id);
    }

    /** Record-page guard counterpart of scopeVisibleTo(). */
    public function isVisibleTo(User $user): bool
    {
        if ($user->can('records.view-all')) {
            return true;
        }

        return static::query()->whereKey($this->getKey())->visibleTo($user)->exists();
    }
}
