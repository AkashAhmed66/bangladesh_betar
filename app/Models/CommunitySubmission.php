<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * M26 — one unified inbox for listener-submitted items: abuse reports,
 * content issues and general feedback. Each row carries a `type`, an optional
 * polymorphic `subject` (the reported/affected item) and a shared status
 * workflow (new → in_progress → resolved / dismissed).
 */
class CommunitySubmission extends Model
{
    protected $guarded = [];

    protected $casts = ['handled_at' => 'datetime'];

    /** Submission kinds → human label (also the inbox "Type" filter). */
    public const TYPES = [
        'content_report' => 'Abuse report',
        'issue_report' => 'Content issue',
        'feedback' => 'Feedback',
    ];

    /** Shared status workflow across every kind. */
    public const STATUSES = ['new', 'in_progress', 'resolved', 'dismissed'];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucwords(str_replace('_', ' ', (string) $this->type));
    }

    /** Who submitted it (nullable — guests may submit issues/feedback). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The reported/affected item — a Comment or AudioAsset (null for feedback). */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
