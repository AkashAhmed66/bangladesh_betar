<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditSession extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = ['edl' => 'array'];

    public function audioAsset(): BelongsTo
    {
        return $this->belongsTo(AudioAsset::class);
    }

    public function sourceVersion(): BelongsTo
    {
        return $this->belongsTo(AudioVersion::class, 'source_version_id');
    }

    public function outputVersion(): BelongsTo
    {
        return $this->belongsTo(AudioVersion::class, 'output_version_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
