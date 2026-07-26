<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AudioAsset;
use App\Models\Banner;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * M24 — promotional banners and a few seeded listener playlists.
 */
class CurationSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Listener playlists (created by users in the public app) ----
        // A few realistic listener collections so the admin Playlists view has
        // content. (Editorial/curated playlists were removed from the product.)
        $collections = [
            ['Songs of 1971', 'একাত্তরের গান', 'Patriotic songs from the Liberation War era.', ['patriotic', 'nazrul-sangeet']],
            ['Golden Age of Radio Drama', 'বেতার নাটকের স্বর্ণযুগ', 'Classic radio dramas restored from the archive.', []],
            ['Rainy Day Folk', 'বর্ষার লোকগীতি', 'Folk melodies for the monsoon.', ['folk']],
        ];

        $owners = User::query()->inRandomOrder()->pluck('id');

        foreach ($collections as $i => [$title, $titleBn, $description, $genreSlugs]) {
            $playlist = Playlist::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'user_id' => $owners->isNotEmpty() ? $owners[$i % $owners->count()] : null,
                    'title' => $title,
                    'title_bn' => $titleBn,
                    'description' => $description,
                    'is_editorial' => false,
                    'is_public' => true,
                    'is_published' => true,
                    'followers_count' => random_int(20, 900),
                ],
            );

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

        // Home rows are now static in the API (BrowseController@home) — no
        // longer admin-managed, so nothing to seed here.

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

        $this->command?->info('Curation: '.count($collections).' listener playlists, '.count($banners).' banners seeded');
    }
}
