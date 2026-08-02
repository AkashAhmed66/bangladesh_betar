<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RightsRecord extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'rights_types' => 'array',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'royalty_required' => 'boolean',
        'documents' => 'array', // [{path, name}] on the private disk (FR-CPR-02)
    ];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function rightsHolder(): BelongsTo
    {
        return $this->belongsTo(RightsHolder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->where('status', 'approved')
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast();
    }
}
