<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'usage_rights_start' => 'date',
        'usage_rights_end' => 'date',
    ];

    public function finalAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class, 'final_asset_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(CampaignAsset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
