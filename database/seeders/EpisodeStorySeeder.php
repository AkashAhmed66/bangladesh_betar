<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Programme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Database\Seeders\Concerns\CreatesAudioAssets;

/**
 * M10 — Bhoot FM style event-programme episodes.
 */
class EpisodeStorySeeder extends Seeder
{
    use CreatesAudioAssets;

    public function run(): void
    {
        $bhootFm = Programme::query()->where('slug', 'bhoot-fm')->first();
        if (! $bhootFm) {
            return;
        }

        $eventCategory = Category::query()->where('slug', 'event-programmes')->value('id');

        $episodes = [
            'Bhoot FM — Episode 101: The Haunted Rest House',
            'Bhoot FM — Episode 102: Night Train to Sylhet',
            'Bhoot FM — Episode 103: The Old Zamindar House',
        ];

        $number = 101;
        foreach ($episodes as $title) {
            $asset = $this->makeAsset($title, [
                'content_type' => 'drama',
                'programme_id' => $bhootFm->id,
                'category_id' => $eventCategory,
                'duration_seconds' => random_int(4800, 7200),
                'content_warning' => 'Contains frightening themes. Listener discretion advised.',
            ]);

            Episode::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'programme_id' => $bhootFm->id,
                    'season_number' => 1,
                    'audio_asset_id' => $asset->id,
                    'number' => $number,
                    'title' => $title,
                    'broadcast_date' => now()->subWeeks(120 - $number)->toDateString(),
                    'duration_seconds' => $asset->duration_seconds,
                    'is_published' => true,
                    'published_at' => now()->subWeeks(120 - $number),
                    'play_count' => random_int(20000, 300000),
                ],
            );

            $number++;
        }

        $this->command?->info('Episodes: '.count($episodes).' Bhoot FM episodes seeded');
    }
}
