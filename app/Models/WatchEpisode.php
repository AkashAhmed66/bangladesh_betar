<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class WatchEpisode extends Model
{
    use Auditable, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'audio_languages' => 'array',
        'subtitle_languages' => 'array',
    ];

    public function show(): BelongsTo
    {
        return $this->belongsTo(WatchShow::class, 'watch_show_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'ratable');
    }

    public function watchlistItems(): MorphMany
    {
        return $this->morphMany(WatchlistItem::class, 'watchable');
    }
}
