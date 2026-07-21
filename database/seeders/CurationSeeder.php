<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\Banner;
use App\Models\HomeSection;
use App\Models\HomeSectionItem;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\PodcastChannel;
use App\Models\Song;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * M24 — home sections, banners and editorially curated collections.
 */
class CurationSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Editorial playlists (curated collections, FR-CUR-02) ----
        $collections = [
            ['Songs of 1971', 'একাত্তরের গান', 'Patriotic songs from the Liberation War era.', ['patriotic', 'nazrul-sangeet']],
            ['Golden Age of Radio Drama', 'বেতার নাটকের স্বর্ণযুগ', 'Classic radio dramas restored from the archive.', []],
            ['Rainy Day Folk', 'বর্ষার লোকগীতি', 'Folk melodies for the monsoon.', ['folk']],
        ];

        $playlists = [];
        foreach ($collections as [$title, $titleBn, $description, $genreSlugs]) {
            $playlist = Playlist::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'user_id' => null,
                    'title' => $title,
                    'title_bn' => $titleBn,
                    'description' => $description,
                    'is_editorial' => true,
                    'is_public' => true,
                    'is_published' => true,
                    'followers_count' => random_int(200, 9000),
                ],
            );
            $playlists[] = $playlist;

            $songs = Song::query()
                ->when($genreSlugs !== [], fn ($q) => $q->whereHas('genre', fn ($g) => $g->whereIn('slug', $genreSlugs)))
                ->take(6)->get();

            $dramaAssets = $genreSlugs === []
                ? AudioAsset::query()->where('content_type', 'drama')->where('status', 'published')->take(4)->get()
                : collect();

            $position = 0;
            $playlist->items()->delete();
            foreach ($songs as $song) {
                PlaylistItem::query()->create([
                    'playlist_id' => $playlist->id,
                    'playable_type' => 'song',
                    'playable_id' => $song->id,
                    'position' => $position++,
                ]);
            }
            foreach ($dramaAssets as $asset) {
                PlaylistItem::query()->create([
                    'playlist_id' => $playlist->id,
                    'playable_type' => 'audio_asset',
                    'playable_id' => $asset->id,
                    'position' => $position++,
                ]);
            }
        }

        // ---- Home sections (FR-CUR-01) ----
        $sections = [
            ['Trending Now', 'এখন ট্রেন্ডিং', 'trending', 'row', 1, null],
            ['New Releases', 'নতুন প্রকাশ', 'new_releases', 'row', 2, null],
            ['Curated Collections', 'নির্বাচিত সংগ্রহ', 'curated_playlists', 'row', 3, 'playlist'],
            ['On This Day', 'ইতিহাসের এই দিনে', 'on_this_day', 'row', 4, null],
            ['Top Played', 'সর্বাধিক শোনা', 'top_played', 'row', 5, null],
        ];

        foreach ($sections as [$title, $titleBn, $type, $layout, $position, $curatableType]) {
            $section = HomeSection::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'title_bn' => $titleBn,
                    'section_type' => $type,
                    'layout' => $layout,
                    'position' => $position,
                    'max_items' => 12,
                    'is_active' => true,
                ],
            );

            if ($curatableType === null) {
                continue; // dynamic sections resolve content at request time
            }

            $models = match ($curatableType) {
                'playlist' => collect($playlists),
                'artist' => Artist::query()->where('is_featured', true)->get(),
                'album' => Album::query()->where('is_published', true)->get(),
                'podcast_channel' => PodcastChannel::query()->where('is_published', true)->get(),
                default => collect(),
            };

            $section->items()->delete();
            foreach ($models->values() as $index => $model) {
                HomeSectionItem::query()->create([
                    'home_section_id' => $section->id,
                    'curatable_type' => $curatableType,
                    'curatable_id' => $model->id,
                    'position' => $index,
                ]);
            }
        }

        // A scheduled seasonal section (FR-CUR-03).
        HomeSection::query()->updateOrCreate(
            ['slug' => 'victory-day-special'],
            [
                'title' => 'Victory Day Special',
                'title_bn' => 'বিজয় দিবস বিশেষ',
                'section_type' => 'custom',
                'layout' => 'banner',
                'position' => 0,
                'is_active' => true,
                'starts_at' => now()->parse('December 10')->startOfDay(),
                'ends_at' => now()->parse('December 18')->endOfDay(),
                'filters' => ['tag' => 'victory-day'],
            ],
        );

        // ---- Banners ----
        $banners = [
            ['Discover the Liberation War Archive', 'মুক্তিযুদ্ধের আর্কাইভ আবিষ্কার করুন', 'Rare recordings from 1971, digitized and restored.', 1],
            ['Go Premium — Listen Without Limits', 'প্রিমিয়াম নিন — সীমাহীন শুনুন', 'Ad-free, high quality and offline listening.', 2],
            ['Bhoot FM Returns', 'ভূত এফএম ফিরে এলো', 'New episodes of the legendary horror show.', 3],
        ];

        foreach ($banners as [$title, $titleBn, $subtitle, $position]) {
            Banner::query()->updateOrCreate(
                ['title' => $title],
                [
                    'title_bn' => $titleBn,
                    'subtitle' => $subtitle,
                    'position' => $position,
                    'is_active' => true,
                    'target_type' => 'url',
                    'target_value' => '/browse',
                ],
            );
        }

        $this->command?->info('Curation: '.count($collections).' collections, '.(count($sections) + 1).' sections, '.count($banners).' banners seeded');
    }
}
