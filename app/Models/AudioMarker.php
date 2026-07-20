<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioMarker extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_seconds' => 'float',
        'end_seconds' => 'float',
        'is_ai' => 'boolean',
    ];

    /** Default colour per marker type for the timeline. */
    public const COLORS = [
        'chapter' => '#0ea5e9', 'keyword' => '#8b5cf6', 'speaker' => '#14b8a6',
        'segment' => '#64748b', 'applause' => '#f59e0b', 'laughter' => '#f59e0b',
        'music' => '#10b981', 'speech' => '#0d9488', 'silence' => '#94a3b8',
        'ad' => '#ef4444', 'intro' => '#6366f1', 'outro' => '#6366f1',
        'emotion' => '#ec4899', 'note' => '#d97706',
    ];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolvedColor(): string
    {
        return $this->color ?? self::COLORS[$this->marker_type] ?? '#64748b';
    }
}
