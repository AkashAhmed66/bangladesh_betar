<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Episode extends Model
{
    use Auditable, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'broadcast_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /* ------------------------------ search ---------------------------- */

    /** Published programme episodes that actually have audio attached. */
    public function shouldBeSearchable(): bool
    {
        return (bool) $this->is_published && $this->audio_asset_id !== null;
    }

    public function toSearchableArray(): array
    {
        $asset = $this->audioAsset;

        return [
            'type' => 'episode',
            'entity_id' => $this->id,
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'people' => [],
            'body' => $this->description,
            'body_bn' => null,
            'transcript' => $asset?->transcripts->pluck('full_text')->filter()->implode(' '),
            'popularity' => (int) ($this->play_count ?? 0),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['audioAsset.transcripts']);
    }
}
