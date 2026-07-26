<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One submission of an uploaded asset to the external audio-postmortem
 * service (duplicate / violence / anti-government detection + transcription).
 * `status` tracks the external job's own lifecycle; `review_status` tracks
 * the separate AI Reviewer sign-off when the result flagged the content.
 */
class AiAnalysisJob extends Model
{
    protected $guarded = [];

    protected $casts = [
        'has_audio_fingerprint' => 'boolean',
        'is_duplicate' => 'boolean',
        'violence_detected' => 'boolean',
        'anti_government_detected' => 'boolean',
        'violence_evidence' => 'array',
        'anti_government_evidence' => 'array',
        'raw_response' => 'array',
        'duration_sec' => 'float',
        'audio_match_pct' => 'float',
        'content_match_pct' => 'float',
        'violence_confidence' => 'float',
        'anti_government_confidence' => 'float',
        'matched_uploaded_at' => 'datetime',
        'polled_at' => 'datetime',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', 'pending');
    }

    /** Jobs where any of the three checks tripped (the AI moderation queue). */
    public function scopeFlagged(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('is_duplicate', true)
                ->orWhere('violence_detected', true)
                ->orWhere('anti_government_detected', true);
        });
    }

    /** True once the external job finished and any of the three checks tripped. */
    public function isFlagged(): bool
    {
        return (bool) $this->is_duplicate || (bool) $this->violence_detected || (bool) $this->anti_government_detected;
    }
}
