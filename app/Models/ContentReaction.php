<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ContentReaction extends Model
{
    public const LIKE = 'like';

    public const DISLIKE = 'dislike';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
