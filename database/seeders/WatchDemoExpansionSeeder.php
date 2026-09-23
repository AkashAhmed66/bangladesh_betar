<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\WatchCategory;
use App\Models\WatchShow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Adds a small, varied Watch catalogue for local/demo environments.
 *
 * This is intentionally separate from PortalContentSeeder so it can be run
 * safely on an existing installation without replacing editorial content.
 */
final class WatchDemoExpansionSeeder extends Seeder
{
    public function run(): void
    {
        $this->installAssets();

        $creatorId = User::query()->where('email', 'admin@betar.gov.bd')->value('id');
        $categories = WatchCategory::query()->pluck('id', 'slug');

        $shows = [
            [
                'slug' => 'green-futures-bangladesh',
                'title' => 'Green Futures Bangladesh',
                'title_bn' => 'সবুজ ভবিষ্যৎ বাংলাদেশ',
                'eyebrow' => 'Innovation Documentary',
                'eyebrow_bn' => 'উদ্ভাবনভিত্তিক প্রামাণ্যচিত্র',
                'description' => 'Young engineers turn practical climate ideas into affordable solutions for farming communities.',
                'description_bn' => 'তরুণ প্রকৌশলীরা কৃষক সম্প্রদায়ের জন্য সাশ্রয়ী জলবায়ু সমাধান তৈরি করছেন।',
                'category' => 'Documentary',
                'image' => 'watch-innovation.png',
                'year' => 2026,
                'rating' => 'G',
                'featured' => true,
                'episodes' => [
                    ['Solar irrigation from a village workshop', 'গ্রামের কর্মশালায় সৌর সেচ', 24, 'A field report on a solar pump that helps farmers irrigate without diesel.', 'portal/demo/watch-solar.mp4'],
                    ['The makers behind the machine', 'যন্ত্রটির পেছনের নির্মাতারা', 27, 'Meet the students and farmers testing a cleaner way to grow food.', 'portal/demo/episodes/green-futures-bangladesh-2.mp4'],
                ],
            ],
            [
                'slug' => 'sundarbans-field-notes',
                'title' => 'Sundarbans Field Notes',
                'title_bn' => 'সুন্দরবনের মাঠ-নোট',
                'eyebrow' => 'Nature & Travel',
                'eyebrow_bn' => 'প্রকৃতি ও ভ্রমণ',
                'description' => 'A field team follows the waterways, wildlife and guardians of the world’s largest mangrove forest.',
                'description_bn' => 'বিশ্বের বৃহত্তম ম্যানগ্রোভ বনের জলপথ, বন্যপ্রাণী ও রক্ষকদের সঙ্গে মাঠ-দলের যাত্রা।',
                'category' => 'Documentary',
                'image' => 'watch-sundarbans.png',
                'year' => 2026,
                'rating' => 'G',
                'featured' => true,
                'episodes' => [
                    ['At the edge of the mangrove', 'ম্যানগ্রোভের প্রান্তে', 31, 'Researchers map changing tides and the people who live beside them.', 'portal/demo/watch-sundarbans.webm'],
                    ['Guardians of the tidal forest', 'জোয়ারের বনের প্রহরী', 29, 'Meet the rangers and honey collectors protecting a fragile ecosystem.', 'portal/demo/episodes/sundarbans-field-notes-2.mp4'],
                ],
            ],
            [
                'slug' => 'studio-sounds',
                'title' => 'Studio Sounds',
                'title_bn' => 'স্টুডিও সাউন্ডস',
                'eyebrow' => 'Music & Culture',
                'eyebrow_bn' => 'সংগীত ও সংস্কৃতি',
                'description' => 'Artists, hosts and instrument makers share the stories behind Bangladesh’s living musical traditions.',
                'description_bn' => 'শিল্পী, উপস্থাপক ও বাদ্যযন্ত্র নির্মাতারা বাংলাদেশের জীবন্ত সংগীত ঐতিহ্যের গল্প বলছেন।',
                'category' => 'Culture',
                'image' => 'watch-studio.png',
                'year' => 2026,
                'rating' => 'G',
                'featured' => false,
                'episodes' => [
                    ['One song, three generations', 'একটি গান, তিন প্রজন্ম', 35, 'A warm studio session connects a folk melody with a new generation of performers.', 'portal/demo/episodes/studio-sounds-1.mp4'],
                    ['Making the dotara sing', 'দোতারা তৈরির গল্প', 22, 'A craftsperson explains the patience and precision behind a beloved instrument.', 'portal/demo/episodes/studio-sounds-2.mp4'],
                ],
            ],
            [
                'slug' => 'the-last-platform',
                'title' => 'The Last Platform',
                'title_bn' => 'শেষ প্ল্যাটফর্ম',
                'eyebrow' => 'Mystery Drama',
                'eyebrow_bn' => 'রহস্য নাটক',
                'description' => 'A late-night railway mystery unfolds through forgotten notebooks, rain-soaked tracks and one final transmission.',
                'description_bn' => 'বৃষ্টিভেজা রেললাইন, পুরোনো নোটবুক ও শেষ সম্প্রচারের মধ্য দিয়ে উন্মোচিত হয় এক মধ্যরাতের রহস্য।',
                'category' => 'Crime Drama',
                'image' => 'watch-noir.png',
                'year' => 2026,
                'rating' => '13+',
                'featured' => false,
                'episodes' => [
                    ['The notebook on platform four', 'চার নম্বর প্ল্যাটফর্মের নোটবুক', 42, 'A detective discovers a pattern in the final notes of a missing broadcaster.', 'portal/demo/episodes/the-last-platform-1.mp4'],
                    ['Signal in the rain', 'বৃষ্টির মধ্যে সংকেত', 44, 'An impossible signal leads the investigation toward an abandoned station.', 'portal/demo/episodes/the-last-platform-2.mp4'],
                ],
            ],
        ];

        foreach ($shows as $position => $data) {
            $categoryId = $categories->get(strtolower(str_replace(' ', '-', $data['category'])));

            $expectedImagePath = 'portal/demo/'.$data['image'];
            $show = WatchShow::query()->where('slug', $data['slug'])->first();

            // A matching slug can be an administrator's real programme. Never
            // replace its artwork or editorial metadata with demo content.
            if ($show !== null && $show->image_path !== $expectedImagePath) {
                continue;
            }

            $defaults = [
                'slug' => $data['slug'],
                'created_by' => $creatorId,
                'watch_category_id' => $categoryId,
                'title' => $data['title'],
                'title_bn' => $data['title_bn'],
                'eyebrow' => $data['eyebrow'],
                'eyebrow_bn' => $data['eyebrow_bn'],
                'description' => $data['description'],
                'description_bn' => $data['description_bn'],
                'category' => $data['category'],
                'image_path' => $expectedImagePath,
                'year' => $data['year'],
                'rating' => $data['rating'],
                'position' => $position,
                'is_featured' => $data['featured'],
                'is_published' => true,
                'published_at' => now()->subDays($position + 1),
            ];

            if ($show === null) {
                $show = WatchShow::query()->create($defaults);
            } else {
                // Fill only fields that were not supplied yet. This makes the
                // seeder safe for Docker restarts and preserves editorial edits.
                $changed = false;
                foreach ($defaults as $attribute => $value) {
                    if (blank($show->getAttribute($attribute))) {
                        $show->setAttribute($attribute, $value);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $show->save();
                }
            }

            foreach ($data['episodes'] as $episodePosition => $episode) {
                [$title, $titleBn, $duration, $description, $videoPath] = $episode;
                $values = [
                    'title_bn' => $titleBn,
                    'description' => $description,
                    'duration_minutes' => $duration,
                    'position' => $episodePosition + 1,
                    'is_published' => true,
                ];

                // Only demo episodes with a supplied asset update video_path;
                // existing editorial uploads are never cleared by this seeder.
                if ($videoPath !== null) {
                    $values['video_path'] = $videoPath;
                }

                $existingEpisode = $show->episodes()->where('position', $episodePosition + 1)->first();
                if ($existingEpisode === null) {
                    $show->episodes()->create($values + ['title' => $title]);
                } else {
                    $changed = false;
                    foreach ($values as $attribute => $value) {
                        if (blank($existingEpisode->getAttribute($attribute))) {
                            $existingEpisode->setAttribute($attribute, $value);
                            $changed = true;
                        }
                    }
                    if ($changed) {
                        $existingEpisode->save();
                    }
                }
            }
        }

        $this->command?->info('Watch demo expansion seeded (4 shows, 8 episodes with sample video).');
    }

    private function installAssets(): void
    {
        $disk = Storage::disk('public');
        $sourceDirectory = base_path('database/seeders/assets/portal');
        $assets = [
            'watch-innovation.png' => 'watch-innovation.png',
            'watch-sundarbans.png' => 'watch-sundarbans.png',
            'watch-studio.png' => 'watch-studio.png',
            'watch-noir.png' => 'watch-noir.png',
            'watch-solar.mp4' => 'watch-solar.mp4',
            'watch-sundarbans.webm' => 'watch-sundarbans.webm',
            'episodes/green-futures-bangladesh-2.mp4' => 'watch-solar.mp4',
            'episodes/sundarbans-field-notes-2.mp4' => 'watch-solar.mp4',
            'episodes/studio-sounds-1.mp4' => 'watch-solar.mp4',
            'episodes/studio-sounds-2.mp4' => 'watch-solar.mp4',
            'episodes/the-last-platform-1.mp4' => 'watch-solar.mp4',
            'episodes/the-last-platform-2.mp4' => 'watch-solar.mp4',
        ];

        foreach ($assets as $targetName => $sourceName) {
            $path = $sourceDirectory.'/'.$sourceName;
            if (! File::exists($path) || File::size($path) === 0) {
                throw new RuntimeException('Watch demo asset is missing or empty: '.$sourceName);
            }

            $target = 'portal/demo/'.$targetName;
            if (! $disk->exists($target) && ! $disk->put($target, File::get($path), 'public')) {
                throw new RuntimeException('Unable to install Watch demo asset: '.$targetName);
            }
        }
    }
}
