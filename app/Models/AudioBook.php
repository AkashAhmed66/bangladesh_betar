<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An Audio Book (M31): PDF/text narrated in BOTH male and female voices,
 * approved by an audiobook approver, then published to premium listeners
 * with read-along text.
 */
class AudioBook extends Model
{
    protected $guarded = [];

    protected $casts = [
        'used_ocr' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isPending(): bool
    {
        return $this->status === 'generating';
    }

    public function isReadyForSubmission(): bool
    {
        return in_array($this->status, ['ready', 'rejected', 'unpublished'], true);
    }
}
