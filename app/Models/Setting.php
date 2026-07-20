<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('app_settings'));
        static::deleted(fn () => Cache::forget('app_settings'));
    }

    /** All settings as a cached key => typed value map. */
    public static function allCached(): array
    {
        return Cache::rememberForever('app_settings', function (): array {
            return static::query()->get()->mapWithKeys(function (self $setting): array {
                return [$setting->key => $setting->typedValue()];
            })->toArray();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allCached()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string', ?string $label = null): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'type' => $type,
                'label' => $label,
                'value' => $type === 'json' ? json_encode($value) : (string) $value,
            ],
        );
    }

    public function typedValue(): mixed
    {
        return match ($this->type) {
            'int' => (int) $this->value,
            'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }
}
