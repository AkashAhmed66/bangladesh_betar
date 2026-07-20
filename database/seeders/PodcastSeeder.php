<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Category;
use App\Models\Language;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Database\Seeders\Concerns\CreatesAudioAssets;

/**
 * M09 — podcast channels, seasons/episodes, hosts and scheduling.
 */
class PodcastSeeder extends Seeder
{
    use CreatesAudioAssets;

    public function run(): void
    {
        $owner = User::query()->where('email', 'podcast@betar.gov.bd')->first();
        $podcastCategory = Category::query()->where('slug', 'podcasts')->value('id');
        $bn = Language::query()->where('code', 'bn')->value('id');

        $channels = [
            [
                'title' => 'Betar Itihash',
                'title_bn' => 'বেতার ইতিহাস',
                'description' => 'The story of radio broadcasting in Bangladesh, told through the archive.',
                'episodes' => [
                    ['The Birth of Radio Dhaka', 1, 1, false],
                    ['Broadcasting Through 1971', 1, 2, false],
                    ['The Golden Age of Radio Drama', 1, 3, false],
                    ['Voices That Shaped a Nation', 2, 1, true],
                ],
            ],
            [
                'title' => 'Shobder Golpo',
                'title_bn' => 'শব্দের গল্প',
                'description' => 'Interviews and storytelling with the artists behind the microphone.',
                'episodes' => [
                    ['A Folk Singer Remembers', 1, 1, false],
                    ['Behind the Drama Curtain', 1, 2, false],
                    ['The Sound Engineers', 1, 3, true],
                ],
            ],
            [
                'title' => 'Betar Science Café',
                'title_bn' => 'বেতার সায়েন্স ক্যাফে',
                'description' => 'Science and curiosity for young listeners, in Bangla.',
                'episodes' => [
                    ['Why the River Bends', 1, 1, false],
                    ['Monsoon Machines', 1, 2, false],
                ],
            ],
        ];

        $host = Artist::query()->where('artist_type', 'presenter')->first();
        $guest = Artist::query()->where('artist_type', 'singer')->first();

        foreach ($channels as $channelData) {
            $channel = PodcastChannel::query()->updateOrCreate(
                ['slug' => Str::slug($channelData['title'])],
                [
                    'title' => $channelData['title'],
                    'title_bn' => $channelData['title_bn'],
                    'description' => $channelData['description'],
                    'category_id' => $podcastCategory,
                    'language_id' => $bn,
                    'owner_id' => $owner?->id,
                    'rss_enabled' => true,
                    'is_published' => true,
                    'followers_count' => random_int(500, 20000),
                ],
            );

            foreach ($channelData['episodes'] as [$title, $seasonNo, $episodeNo, $premium]) {
                $asset = $this->makeAsset($channelData['title'].' — '.$title, [
                    'content_type' => 'podcast',
                    'category_id' => $podcastCategory,
                    'duration_seconds' => random_int(1200, 3600),
                    'is_premium' => $premium,
                ]);

                PodcastEpisode::query()->updateOrCreate(
                    ['slug' => Str::slug($channelData['title'].' '.$title)],
                    [
                        'podcast_channel_id' => $channel->id,
                        'audio_asset_id' => $asset->id,
                        'season_number' => $seasonNo,
                        'episode_number' => $episodeNo,
                        'title' => $title,
                        'description' => "$title — an episode of {$channelData['title']} from Bangladesh Betar.",
                        'duration_seconds' => $asset->duration_seconds,
                        'is_premium' => $premium,
                        'status' => 'published',
                        'published_at' => now()->subWeeks(random_int(1, 40)),
                        'chapters' => [
                            ['title' => 'Introduction', 'start_seconds' => 0],
                            ['title' => 'Main Segment', 'start_seconds' => 180],
                            ['title' => 'Closing Notes', 'start_seconds' => max(300, $asset->duration_seconds - 240)],
                        ],
                        'play_count' => random_int(2000, 80000),
                    ],
                )->artists()->syncWithoutDetaching([
                    $host?->id => ['role' => 'host'],
                    $guest?->id => ['role' => 'guest'],
                ]);
            }
        }

        // One scheduled episode to exercise FR-POD-03.
        $channel = PodcastChannel::query()->where('slug', 'betar-itihash')->first();
        if ($channel) {
            $asset = $this->makeAsset('Betar Itihash — The Shortwave Years', [
                'content_type' => 'podcast',
                'category_id' => $podcastCategory,
                'status' => 'approved',
                'access_level' => 'internal',
                'published_at' => null,
                'play_count' => 0,
            ]);
            PodcastEpisode::query()->updateOrCreate(
                ['slug' => 'betar-itihash-the-shortwave-years'],
                [
                    'podcast_channel_id' => $channel->id,
                    'audio_asset_id' => $asset->id,
                    'season_number' => 2,
                    'episode_number' => 2,
                    'title' => 'The Shortwave Years',
                    'description' => 'Scheduled for future release.',
                    'duration_seconds' => $asset->duration_seconds,
                    'status' => 'scheduled',
                    'scheduled_at' => now()->addDays(7),
                    'play_count' => 0,
                ],
            );
        }

        $this->command?->info('Podcasts: '.count($channels).' channels with episodes seeded');
    }
}
