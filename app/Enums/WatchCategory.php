<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

enum WatchCategory: string
{
    case Live = 'Live TV';
    case Drama = 'Drama';
    case Documentary = 'Documentary';
    case Culture = 'Culture';
    case Kids = 'Kids';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category): array => [$category->value => $category->label()])
            ->all();
    }

    /** @return array<int, array{value: string, label: string, slug: string}> */
    public static function metadata(): array
    {
        return array_map(
            fn (self $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
                'slug' => $category->slug(),
            ],
            self::cases(),
        );
    }

    public static function fromSlug(string $slug): ?self
    {
        return collect(self::cases())->first(
            fn (self $category): bool => $category->slug() === Str::slug($slug),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Culture => 'Culture & music',
            default => $this->value,
        };
    }

    public function slug(): string
    {
        return Str::slug($this->value);
    }
}
