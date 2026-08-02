<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One PDF/text → speech conversion (offline espeak-ng pipeline).
 */
class SpeechConversion extends Model
{
    protected $guarded = [];

    protected $casts = ['used_ocr' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['queued', 'extracting', 'synthesizing'], true);
    }
}
