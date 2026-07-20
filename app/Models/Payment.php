<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'refunded_amount' => 'float',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public static function nextInvoiceNo(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.str_pad((string) ((static::query()->max('id') ?? 0) + 1), 6, '0', STR_PAD_LEFT);
    }
}
