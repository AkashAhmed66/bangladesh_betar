<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function stages(): HasMany
    {
        return $this->hasMany(WorkflowStage::class)->orderBy('sequence');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public static function forContentType(string $contentType): ?self
    {
        return static::query()->where('is_active', true)
            ->where('content_type', $contentType)
            ->first()
            ?? static::query()->where('is_active', true)->where('content_type', 'default')->first();
    }
}
