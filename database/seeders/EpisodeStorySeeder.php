<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Programme;
use App\Models\Story;
use App\Models\StorySubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Database\Seeders\Concerns\CreatesAudioAssets;

/**
 * M10 — Bhoot FM style event programme: episodes divided into
 * individually addressable stories, plus listener submissions.
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

        $storyCategory = fn (string $slug) => Category::query()->where('slug', $slug)->value('id');
        $season = $bhootFm->seasons()->orderByDesc('number')->first();

        $episodes = [
            [
                'title' => 'Bhoot FM — Episode 101: The Haunted Rest House',
                'stories' => [
                    ['The Rest House Caretaker', 'horror', 'Kushtia', 'Anonymous caller from Kushtia', true],
                    ['Footsteps on the Stairs', 'paranormal-experience', 'Jessore', 'Mahmud from Jessore', false],
                    ['The Banyan Tree', 'village-tales', 'Barisal', 'Rokeya from Barisal', false],
                ],
            ],
            [
                'title' => 'Bhoot FM — Episode 102: Night Train to Sylhet',
                'stories' => [
                    ['The Empty Compartment', 'horror', 'Sylhet', 'Anwar from Sylhet', false],
                    ['Signal Man of Srimangal', 'paranormal-experience', 'Moulvibazar', 'Anonymous railway worker', true],
                ],
            ],
            [
                'title' => 'Bhoot FM — Episode 103: The Old Zamindar House',
                'stories' => [
                    ['Mirror in the Attic', 'horror', 'Rajshahi', 'Shila from Rajshahi', false],
                    ['The Last Lantern', 'village-tales', 'Natore', 'Karim Sheikh', false],
                    ['Whispers at Midnight', 'paranormal-experience', 'Pabna', 'Anonymous student', true],
                ],
            ],
            [
                'title' => 'Bhoot FM — Episode 104: Monsoon Special',
                'stories' => [
                    ['The Ferryman of Padma', 'village-tales', 'Rajbari', 'Boatman Jalil', false],
                    ['Rain on the Tin Roof', 'horror', 'Mymensingh', 'Tuhin from Mymensingh', false],
                ],
            ],
        ];

        $number = 101;
        foreach ($episodes as $data) {
            $asset = $this->makeAsset($data['title'], [
                'content_type' => 'story',
                'programme_id' => $bhootFm->id,
                'category_id' => $storyCategory('event-programmes'),
                'duration_seconds' => random_int(4800, 7200),
                'content_warning' => 'Contains frightening themes. Listener discretion advised.',
            ]);

            $episode = Episode::query()->updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'programme_id' => $bhootFm->id,
                    'season_id' => $season?->id,
                    'audio_asset_id' => $asset->id,
                    'number' => $number,
                    'title' => $data['title'],
                    'broadcast_date' => now()->subWeeks(120 - $number)->toDateString(),
                    'duration_seconds' => $asset->duration_seconds,
                    'is_published' => true,
                    'published_at' => now()->subWeeks(120 - $number),
                    'play_count' => random_int(20000, 300000),
                ],
            );

            $cursor = 0;
            foreach ($data['stories'] as [$title, $catSlug, $district, $storyteller, $anonymous]) {
                $length = intdiv($asset->duration_seconds, count($data['stories']));
                Story::query()->updateOrCreate(
                    ['slug' => Str::slug($data['title'].' '.$title)],
                    [
                        'episode_id' => $episode->id,
                        'category_id' => $storyCategory($catSlug),
                        'title' => $title,
                        'summary' => "A chilling account: $title, as told on Bhoot FM.",
                        'storyteller_name' => $storyteller,
                        'is_anonymous' => $anonymous,
                        'narrator' => 'Russell Chowdhury',
                        'location' => $district,
                        'district' => $district,
                        'start_seconds' => $cursor,
                        'end_seconds' => $cursor + $length,
                        'content_warning' => 'Frightening content',
                        'is_published' => true,
                        'play_count' => random_int(5000, 90000),
                    ],
                );
                $cursor += $length;
            }

            $number++;
        }

        // Listener story submissions with consent records (FR-EVT-05).
        $listener = User::query()->where('email', 'listener1@example.com')->first();
        $submissions = [
            ['The Well Behind Our School', 'text', false],
            ['Voices from the Jute Field', 'audio', true],
            ['My Grandmother\'s Lamp', 'text', false],
        ];

        foreach ($submissions as [$title, $type, $anonymous]) {
            StorySubmission::query()->firstOrCreate(
                ['submitter_name' => $title], // reuse column as a stable key for idempotency
                [
                    'user_id' => $listener?->id,
                    'contact' => $anonymous ? null : 'listener1@example.com',
                    'is_anonymous' => $anonymous,
                    'submission_type' => $type,
                    'content_text' => $type === 'text' ? "Story draft: $title — submitted by a listener for Bhoot FM." : null,
                    'file_path' => $type === 'audio' ? 'submissions/'.Str::slug($title).'.m4a' : null,
                    'consent_given' => true,
                    'consent_at' => now()->subDays(random_int(2, 30)),
                    'status' => 'pending',
                ],
            );
        }

        $this->command?->info('Episodes & stories: '.count($episodes).' Bhoot FM episodes with stories + submissions seeded');
    }
}
