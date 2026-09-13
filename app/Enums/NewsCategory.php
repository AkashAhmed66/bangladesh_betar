<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

enum NewsCategory: string
{
    case Bangladesh = 'Bangladesh';
    case Politics = 'Politics';
    case World = 'World';
    case Business = 'Business';
    case Sports = 'Sports';
    case Entertainment = 'Entertainment';
    case Jobs = 'Jobs';
    case Lifestyle = 'Lifestyle';
    case Video = 'Video';
    case Economy = 'Economy';
    case Climate = 'Climate';
    case Culture = 'Culture';
    case Science = 'Science';
    case Environment = 'Environment';
    case Media = 'Media';

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
        return $this->value;
    }

    public function slug(): string
    {
        return Str::slug($this->value);
    }
}
