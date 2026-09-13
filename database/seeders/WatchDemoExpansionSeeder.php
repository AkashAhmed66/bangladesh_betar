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
                    ['The makers behind the machine', 'যন্ত্রটির পেছনের নির্মাতারা', 27, 'Meet the students and farmers testing a cleaner way to grow food.', null],
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
                    ['Guardians of the tidal forest', 'জোয়ারের বনের প্রহরী', 29, 'Meet the rangers and honey collectors protecting a fragile ecosystem.', null],
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
                    ['One song, three generations', 'একটি গান, তিন প্রজন্ম', 35, 'A warm studio session connects a folk melody with a new generation of performers.', null],
                    ['Making the dotara sing', 'দোতারা তৈরির গল্প', 22, 'A craftsperson explains the patience and precision behind a beloved instrument.', null],
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
                    ['The notebook on platform four', 'চার নম্বর প্ল্যাটফর্মের নোটবুক', 42, 'A detective discovers a pattern in the final notes of a missing broadcaster.', null],
                    ['Signal in the rain', 'বৃষ্টির মধ্যে সংকেত', 44, 'An impossible signal leads the investigation toward an abandoned station.', null],
                ],
            ],
        ];

        foreach ($shows as $position => $data) {
            $categoryId = $categories->get(strtolower(str_replace(' ', '-', $data['category'])));

            $show = WatchShow::query()->updateOrCreate(['slug' => $data['slug']], [
                'created_by' => $creatorId,
                'watch_category_id' => $categoryId,
                'title' => $data['title'],
                'title_bn' => $data['title_bn'],
                'eyebrow' => $data['eyebrow'],
                'eyebrow_bn' => $data['eyebrow_bn'],
                'description' => $data['description'],
                'description_bn' => $data['description_bn'],
                'category' => $data['category'],
                'image_path' => 'portal/demo/'.$data['image'],
                'year' => $data['year'],
                'rating' => $data['rating'],
                'position' => $position,
                'is_featured' => $data['featured'],
                'is_published' => true,
                'published_at' => now()->subDays($position + 1),
            ]);

            foreach ($data['episodes'] as $episodePosition => $episode) {
                [$title, $titleBn, $duration, $description, $videoPath] = $episode;
                $values = [
                    'title_bn' => $titleBn,
                    'description' => $description,
                    'description_bn' => null,
                    'duration_minutes' => $duration,
                    'position' => $episodePosition + 1,
                    'is_published' => true,
                ];

                // Only demo episodes with a supplied asset update video_path;
                // existing editorial uploads are never cleared by this seeder.
                if ($videoPath !== null) {
                    $values['video_path'] = $videoPath;
                }

                $show->episodes()->updateOrCreate(['title' => $title], $values);
            }
        }

        $this->command?->info('Watch demo expansion seeded (4 shows, 8 episodes, 2 video assets).');
    }

    private function installAssets(): void
    {
        $disk = Storage::disk('public');
        $sourceDirectory = base_path('database/seeders/assets/portal');
        $assets = [
            'watch-innovation.png',
            'watch-sundarbans.png',
            'watch-studio.png',
            'watch-noir.png',
            'watch-solar.mp4',
            'watch-sundarbans.webm',
        ];

        foreach ($assets as $asset) {
            $path = $sourceDirectory.'/'.$asset;
            if (! File::exists($path) || File::size($path) === 0) {
                throw new RuntimeException('Watch demo asset is missing or empty: '.$asset);
            }

            $disk->put('portal/demo/'.$asset, File::get($path), 'public');
        }
    }
}
