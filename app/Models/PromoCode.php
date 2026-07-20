<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCode extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isValid(): bool
    {
        return $this->is_active
            && ($this->valid_from === null || ! $this->valid_from->isFuture())
            && ($this->valid_until === null || ! $this->valid_until->isPast())
            && ($this->max_uses === 0 || $this->used_count < $this->max_uses);
    }
}
