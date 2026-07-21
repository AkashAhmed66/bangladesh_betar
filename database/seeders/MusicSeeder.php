<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Mood;
use App\Models\Song;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Database\Seeders\Concerns\CreatesAudioAssets;

/**
 * M08 — albums, songs, version families and artist links.
 */
class MusicSeeder extends Seeder
{
    use CreatesAudioAssets;

    public function run(): void
    {
        $genre = fn (string $slug) => Genre::query()->where('slug', $slug)->value('id');
        $mood = fn (string $slug) => Mood::query()->where('slug', $slug)->value('id');
        $artist = fn (string $name) => Artist::query()->where('name', $name)->first();
        $songCategory = Category::query()->where('slug', 'songs')->value('id');

        $albums = [
            ['Ekattorer Gaan', 'একাত্তরের গান', 1972, 'Patriotic songs recorded during and after the Liberation War.'],
            ['Golden Melodies of Betar', 'বেতারের সোনালী সুর', 1985, 'Signature modern Bangla songs from the golden era.'],
            ['Palli Geeti Collection', 'পল্লীগীতি সংগ্রহ', 1978, 'Folk songs of the rivers and villages of Bengal.'],
            ['Rabindra Smarane', 'রবীন্দ্র স্মরণে', 1990, 'A tribute collection of Rabindra Sangeet.'],
        ];

        $albumModels = [];
        foreach ($albums as [$title, $titleBn, $year, $description]) {
            $albumModels[$title] = Album::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title, 'title_bn' => $titleBn, 'year' => $year,
                    'description' => $description, 'is_published' => true,
                    'is_featured' => $year <= 1980,
                ],
            );
        }

        $songs = [
            // [title, title_bn, album, genre, mood, singer, composer, lyricist, year]
            ['Mora Ekti Phulke Bachabo', 'মোরা একটি ফুলকে বাঁচাবো', 'Ekattorer Gaan', 'patriotic', 'inspirational', 'Alam Chowdhury', 'Ustad Momtaz Ali', 'Jasimuddin Mondal', 1971],
            ['Chol Chol Chol (Archive Recording)', 'চল্‌ চল্‌ চল্‌', 'Ekattorer Gaan', 'nazrul-sangeet', 'energetic', 'Alam Chowdhury', 'Ustad Momtaz Ali', 'Jasimuddin Mondal', 1972],
            ['Nodir Naam Modhumoti', 'নদীর নাম মধুমতী', 'Palli Geeti Collection', 'folk', 'calm', 'Ferdous Ara Begum', 'Ustad Momtaz Ali', 'Jasimuddin Mondal', 1978],
            ['Ei Raat Tomar Amar', 'এই রাত তোমার আমার', 'Golden Melodies of Betar', 'modern-bangla', 'joyful', 'Ferdous Ara Begum', 'Ustad Momtaz Ali', 'Jasimuddin Mondal', 1984],
            ['Akash Bhora Surjo Tara', 'আকাশ ভরা সূর্য তারা', 'Rabindra Smarane', 'rabindra-sangeet', 'nostalgic', 'Ferdous Ara Begum', 'Ustad Momtaz Ali', 'Jasimuddin Mondal', 1990],
        ];

        $order = [];
        foreach ($songs as [$title, $titleBn, $albumTitle, $genreSlug, $moodSlug, $singerName, $composerName, $lyricistName, $year]) {
            $album = $albumModels[$albumTitle];
            $order[$albumTitle] = ($order[$albumTitle] ?? 0) + 1;

            $asset = $this->makeAsset($title, [
                'title_bn' => $titleBn,
                'content_type' => 'song',
                'category_id' => $songCategory,
                'duration_seconds' => random_int(180, 420),
                'description' => "$title — from the album $albumTitle.",
                'first_broadcast_on' => sprintf('%d-%02d-%02d', $year, random_int(1, 12), random_int(1, 28)),
            ]);

            $song = Song::query()->create([
                'audio_asset_id' => $asset->id,
                'album_id' => $album->id,
                'track_number' => $order[$albumTitle],
                'genre_id' => $genre($genreSlug),
                'mood_id' => $mood($moodSlug),
                'version_type' => 'original',
                'release_year' => $year,
                'lyrics' => "Lyrics of $title\n(Preserved from the original Betar songbook archive.)",
                'lyrics_bn' => "$titleBn — গানের কথা\n(বেতার সংগীত আর্কাইভ থেকে সংরক্ষিত।)",
                'mood_genre_verified' => true,
                'is_featured' => $order[$albumTitle] === 1,
            ]);

            foreach ([['singer', $singerName], ['composer', $composerName], ['lyricist', $lyricistName]] as [$role, $name]) {
                if ($a = $artist($name)) {
                    $song->artists()->attach($a->id, ['role' => $role]);
                    $album->artists()->syncWithoutDetaching([$a->id]);
                }
            }
        }

        // A live version to demonstrate song version families (FR-SNG-03).
        $original = Song::query()->whereHas('audioAsset', fn ($q) => $q->where('title', 'Nodir Naam Modhumoti'))->first();
        if ($original) {
            $liveAsset = $this->makeAsset('Nodir Naam Modhumoti (Live 1982)', [
                'content_type' => 'song',
                'category_id' => $songCategory,
                'duration_seconds' => 391,
            ]);
            $live = Song::query()->create([
                'audio_asset_id' => $liveAsset->id,
                'genre_id' => $original->genre_id,
                'mood_id' => $original->mood_id,
                'version_type' => 'live',
                'master_song_id' => $original->id,
                'release_year' => 1982,
                'mood_genre_verified' => true,
            ]);
            $live->artists()->attach(Artist::query()->where('name', 'Alam Chowdhury')->value('id'), ['role' => 'singer']);
        }

        $this->command?->info('Music: '.count($albums).' albums, '.(count($songs) + 1).' songs seeded');
    }
}
