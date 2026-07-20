<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdImpression extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'completed' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function adAsset(): BelongsTo
    {
        return $this->belongsTo(AdAsset::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }
}
