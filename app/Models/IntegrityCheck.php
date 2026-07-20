<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrityCheck extends Model
{
    protected $guarded = [];

    protected $casts = ['checked_at' => 'datetime'];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }
}
