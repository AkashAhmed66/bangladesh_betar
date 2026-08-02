<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasRecordVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    use Auditable, HasRecordVisibility;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'float',
        'targeting' => 'array',
    ];

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(Advertiser::class);
    }

    /** The ad creative — an existing audio asset from the library. */
    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdImpression::class);
    }

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(fn (Builder $q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()->toDateString()))
            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()));
    }
}
