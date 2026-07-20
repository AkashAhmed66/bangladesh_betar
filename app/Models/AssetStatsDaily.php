<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetStatsDaily extends Model
{
    protected $table = 'asset_stats_dailies';

    protected $guarded = [];

    protected $casts = [
        'stat_date' => 'date',
        'heatmap' => 'array',
        'completion_rate' => 'float',
        'skip_rate' => 'float',
        'replay_rate' => 'float',
    ];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }
}
