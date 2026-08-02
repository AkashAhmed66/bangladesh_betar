<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasRecordVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Advertiser extends Model
{
    use Auditable, HasRecordVisibility;

    protected $guarded = [];

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }
}
