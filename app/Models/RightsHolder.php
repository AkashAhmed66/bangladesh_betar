<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RightsHolder extends Model
{
    use Auditable;

    protected $guarded = [];

    public function rightsRecords(): HasMany
    {
        return $this->hasMany(RightsRecord::class);
    }
}
