<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Story extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Public display name honouring the anonymous-storyteller option (FR-EVT-04). */
    public function publicStorytellerName(): string
    {
        return $this->is_anonymous ? 'Anonymous' : ($this->storyteller_name ?? 'Unknown');
    }
}
