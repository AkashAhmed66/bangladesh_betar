<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function audioAssets(): HasMany
    {
        return $this->hasMany(AudioAsset::class);
    }
}
