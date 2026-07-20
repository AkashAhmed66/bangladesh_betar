<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioVersion extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = ['is_default' => 'boolean'];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function derivedFrom(): BelongsTo
    {
        return $this->belongsTo(AudioVersion::class, 'derived_from_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isMaster(): bool
    {
        return $this->version_type === 'preservation_master';
    }
}
