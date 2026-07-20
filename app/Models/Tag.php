<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $guarded = [];

    public function audioAssets(): BelongsToMany
    {
        return $this->belongsToMany(AudioAsset::class, 'audio_asset_tag');
    }
}
