<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WatchEpisode;
use App\Models\WatchShow;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WatchEpisode> */
final class WatchEpisodeFactory extends Factory
{
    protected $model = WatchEpisode::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'watch_show_id' => WatchShow::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'duration_minutes' => fake()->numberBetween(10, 60),
            'position' => 1,
            'is_published' => true,
        ];
    }
}
