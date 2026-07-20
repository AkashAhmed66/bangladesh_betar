<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAsset extends Model
{
    protected $guarded = [];

    protected $casts = ['is_final' => 'boolean'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function voiceArtist(): BelongsTo
    {
        return $this->belongsTo(VoiceArtist::class);
    }

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }
}
