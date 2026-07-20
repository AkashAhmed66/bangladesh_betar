<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PodcastEpisode extends Model
{
    use Auditable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_premium' => 'boolean',
        'chapters' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(PodcastChannel::class, 'podcast_channel_id');
    }

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'podcast_episode_artist')->withPivot('role');
    }

    public function hosts(): BelongsToMany
    {
        return $this->artists()->wherePivot('role', 'host');
    }

    public function guests(): BelongsToMany
    {
        return $this->artists()->wherePivot('role', 'guest');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
