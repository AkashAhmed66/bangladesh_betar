<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasRecordVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaItem extends Model
{
    use Auditable, HasRecordVisibility;

    protected $guarded = [];

    protected $casts = ['digitized_at' => 'datetime'];

    /** Record visibility keys on the registering operator. */
    public static function creatorColumn(): string
    {
        return 'registered_by';
    }

    /** Restricted users see items they registered or digitized. */
    protected function applyVisibility(Builder $query, User $user): void
    {
        $query->where($this->qualifyColumn('registered_by'), $user->id)
            ->orWhere($this->qualifyColumn('digitized_by'), $user->id);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function digitizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'digitized_by');
    }
}
