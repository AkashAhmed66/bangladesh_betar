<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceArtist extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = ['is_available' => 'boolean'];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }
}
