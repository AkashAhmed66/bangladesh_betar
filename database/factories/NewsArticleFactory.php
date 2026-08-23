<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NewsArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NewsArticle> */
final class NewsArticleFactory extends Factory
{
    protected $model = NewsArticle::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'slug' => str($title)->slug(),
            'title' => $title,
            'summary' => fake()->paragraph(),
            'category' => fake()->randomElement(['Bangladesh', 'Economy', 'Climate']),
            'body' => fake()->paragraphs(3),
            'image_path' => 'portal/demo/news-hero.png',
            'read_time_minutes' => 4,
            'is_published' => true,
            'published_at' => now(),
        ];
    }
}
