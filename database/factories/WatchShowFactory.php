<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WatchShow;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WatchShow> */
final class WatchShowFactory extends Factory
{
    protected $model = WatchShow::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'slug' => str($title)->slug(),
            'title' => str($title)->title(),
            'eyebrow' => 'New series',
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['Drama', 'Documentary', 'Culture', 'Kids']),
            'year' => (int) date('Y'),
            'rating' => 'G',
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
