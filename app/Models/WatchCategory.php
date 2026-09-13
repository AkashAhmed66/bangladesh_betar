<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WatchCategory extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_header' => 'boolean',
    ];

    public function shows(): HasMany
    {
        return $this->hasMany(WatchShow::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
