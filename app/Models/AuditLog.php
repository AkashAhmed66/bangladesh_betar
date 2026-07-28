<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Append an immutable entry. Sensitive values are stripped.
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
    ): void {
        $strip = static fn (?array $values): ?array => $values === null ? null
            : collect($values)->except(['password', 'remember_token'])->toArray();

        // `description` is TEXT (see the widen_audit_logs_description migration),
        // but we still cap it so a pathologically long message — e.g. an embedded
        // external error — can never overflow the column and make logging a
        // failure itself throw. The full detail is also retained elsewhere
        // (e.g. ai_analysis_jobs.error).
        $text = $description ?? ($auditable ? class_basename($auditable).' #'.$auditable->getKey() : null);

        static::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $text === null ? null : mb_substr($text, 0, 2000),
            'old_values' => $strip($old),
            'new_values' => $strip($new),
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255) ?: null,
            'created_at' => now(),
        ]);
    }
}
