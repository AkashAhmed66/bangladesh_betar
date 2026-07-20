<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Records create / update / delete events into the append-only audit trail (M21).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model): void {
            AuditLog::record('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model): void {
            AuditLog::record('updated', $model, $model->getOriginal(), $model->getChanges());
        });

        static::deleted(function ($model): void {
            AuditLog::record('deleted', $model, $model->getOriginal(), null);
        });
    }
}
