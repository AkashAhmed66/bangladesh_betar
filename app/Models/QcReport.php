<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcReport extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'checks' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function audioVersion(): BelongsTo
    {
        return $this->belongsTo(AudioVersion::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
