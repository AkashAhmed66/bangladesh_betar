<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WatchEpisode extends Model
{
    use Auditable, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function show(): BelongsTo
    {
        return $this->belongsTo(WatchShow::class, 'watch_show_id');
    }
}
