<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Script extends Model
{
    use Auditable;

    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Script::class, 'parent_script_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Script::class, 'parent_script_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
