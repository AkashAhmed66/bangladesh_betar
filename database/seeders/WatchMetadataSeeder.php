<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WatchEpisode;
use App\Models\WatchShow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Fills metadata on the bundled demo Watch catalogue without touching edits. */
final class WatchMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            'green-futures-bangladesh' => ['Documentary', 'Environment'],
            'sundarbans-field-notes' => ['Documentary', 'Nature'],
            'studio-sounds' => ['Music', 'Culture'],
            'the-last-platform' => ['Drama', 'Mystery'],
        ];

        $credits = [
            'green-futures-bangladesh' => [
                'creators' => ['Demo Documentary Unit (sample)'],
                'cast' => ['Demo field team (sample)'],
                'trailer' => 'watch-solar.mp4',
            ],
            'sundarbans-field-notes' => [
                'creators' => ['Demo Nature Desk (sample)'],
                'cast' => ['Demo field team (sample)'],
                'trailer' => 'watch-sundarbans.webm',
            ],
            'studio-sounds' => [
                'creators' => ['Demo Culture Desk (sample)'],
                'cast' => ['Demo studio ensemble (sample)'],
                'trailer' => 'watch-solar.mp4',
            ],
            'the-last-platform' => [
                'creators' => ['Demo Drama Unit (sample)'],
                'cast' => ['Demo cast (sample)'],
                'trailer' => 'watch-sundarbans.webm',
            ],
        ];
        $episodeSummariesBn = [
            'green-futures-bangladesh' => [
                'গ্রামের কৃষকেরা ডিজেল ছাড়াই সেচ দিতে সৌর পাম্পটি ব্যবহার করছেন।',
                'শিক্ষার্থী ও কৃষকেরা কীভাবে পরিচ্ছন্ন কৃষি প্রযুক্তি পরীক্ষা করছেন, সেই গল্প।',
            ],
            'sundarbans-field-notes' => [
                'গবেষকেরা জোয়ারের পরিবর্তন এবং নদীর পাড়ের মানুষের জীবন নথিবদ্ধ করছেন।',
                'রেঞ্জার ও মধু সংগ্রহকারীরা ভঙ্গুর এই প্রতিবেশ রক্ষার অভিজ্ঞতা জানান।',
            ],
            'studio-sounds' => [
                'একটি লোকগান কীভাবে নতুন প্রজন্মের শিল্পীদের সঙ্গে নতুন জীবন পায়।',
                'প্রিয় দোতারা তৈরির পেছনে কারিগরের ধৈর্য ও নিখুঁত কাজের গল্প।',
            ],
            'the-last-platform' => [
                'নিখোঁজ সম্প্রচারকের শেষ নোটে গোয়েন্দা একটি অদ্ভুত সূত্র খুঁজে পান।',
                'একটি অসম্ভব সংকেত তদন্তকে পরিত্যক্ত স্টেশনের দিকে নিয়ে যায়।',
            ],
        ];

        $this->installTrailers();

        WatchShow::query()->where('image_path', 'like', 'portal/demo/%')->each(function (WatchShow $show) use ($genres, $credits, $episodeSummariesBn): void {
            $showGenres = $genres[$show->slug] ?? array_values(array_filter([$show->category]));
            $creditData = $credits[$show->slug] ?? null;
            $values = [
                'age_restriction' => $show->age_restriction ?: $show->rating,
                'genres' => $show->genres ?: $showGenres,
                'audio_languages' => $show->audio_languages ?: ['Bangla'],
                'subtitle_languages' => $show->subtitle_languages ?: ['English'],
            ];
            // Only these four programmes have bundled sample credits/media.
            if ($creditData !== null) {
                $values['creators'] = $show->creators ?: $creditData['creators'];
                $values['cast'] = $show->cast ?: $creditData['cast'];
                if (! $show->trailer_path) {
                    $values['trailer_path'] = 'portal/demo/trailers/'.$show->slug.'.'.pathinfo($creditData['trailer'], PATHINFO_EXTENSION);
                }
            }
            $show->update($values);

            $show->episodes()->each(function (WatchEpisode $episode) use ($episodeSummariesBn, $show, $creditData): void {
                $summaryBn = $episodeSummariesBn[$show->slug][$episode->position - 1] ?? $episode->description_bn;
                $summary = $episode->summary ?: $episode->description;
                $hasDemoVideo = $creditData !== null && str_starts_with((string) $episode->video_path, 'portal/demo/');
                if ($hasDemoVideo && $summary && (! $episode->summary || $episode->summary === $episode->description)) {
                    $summary .= ' (Seeded demo includes a short preview clip.)';
                }
                if ($hasDemoVideo && ! $episode->summary_bn && $summaryBn) {
                    $summaryBn .= ' (ডেমোতে সংক্ষিপ্ত প্রিভিউ ক্লিপ রয়েছে।)';
                }
                $episode->update([
                    'summary' => $summary,
                    'summary_bn' => $episode->summary_bn ?: $summaryBn,
                    'audio_languages' => $episode->audio_languages ?: ['Bangla'],
                    'subtitle_languages' => $episode->subtitle_languages ?: ['English'],
                ]);
            });
        });

        $this->command?->info('Watch metadata seeded for bundled demo shows.');
    }

    private function installTrailers(): void
    {
        $disk = Storage::disk('public');
        $sourceDirectory = base_path('database/seeders/assets/portal');
        $sources = [
            'green-futures-bangladesh.mp4' => 'watch-solar.mp4',
            'sundarbans-field-notes.webm' => 'watch-sundarbans.webm',
            'studio-sounds.mp4' => 'watch-solar.mp4',
            'the-last-platform.webm' => 'watch-sundarbans.webm',
        ];

        foreach ($sources as $targetName => $sourceName) {
            $source = $sourceDirectory.'/'.$sourceName;
            if (! File::exists($source) || File::size($source) === 0) {
                throw new RuntimeException('Watch demo trailer is missing or empty: '.$sourceName);
            }

            $target = 'portal/demo/trailers/'.$targetName;
            if (! $disk->exists($target) && ! $disk->put($target, File::get($source), 'public')) {
                throw new RuntimeException('Unable to install Watch demo trailer: '.$targetName);
            }
        }
    }
}
