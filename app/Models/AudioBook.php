<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

/**
 * An Audio Book (M31): PDF/text narrated in BOTH male and female voices,
 * approved by an audiobook approver, then published to premium listeners
 * with read-along text.
 */
class AudioBook extends Model
{
    use Searchable;

    protected $guarded = [];

    protected $casts = [
        'used_ocr' => 'boolean',
        'text_edited' => 'boolean',
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

    /* ------------------------------ search ---------------------------- */

    /** Only publicly available books belong in the search index. */
    public function shouldBeSearchable(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Index the title, author and complete read-along text. The primary fields
     * are always populated; Bangla books are additionally copied into the
     * Bengali-analyzed fields so both scripts are matched correctly.
     */
    public function toSearchableArray(): array
    {
        $isBangla = $this->language === 'bn';

        return [
            'type' => 'audio_book',
            'entity_id' => $this->id,
            'title' => $this->title,
            'title_bn' => $isBangla ? $this->title : null,
            'people' => array_values(array_filter([$this->user?->name])),
            'body' => $this->text,
            'body_bn' => $isBangla ? $this->text : null,
            'transcript' => null,
            'popularity' => 0,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    /** Eager-load the author required by the searchable payload. */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('user');
    }
}
