<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\AudioBook;
use App\Models\Banner;
use App\Models\BroadcastChannel;
use App\Models\Episode;
use App\Models\Playlist;
use App\Models\PodcastChannel;
use App\Models\PodcastEpisode;
use App\Models\Programme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Replaces the original one-image-per-module demo artwork with a varied,
 * modern Listen catalogue. Real artwork uploaded through the admin portal is
 * deliberately preserved; only empty fields and the old demo-artwork paths
 * are eligible for replacement.
 */
final class ListenArtworkExpansionSeeder extends Seeder
{
    private const DIRECTORY = 'listen-artwork';

    private const COVERS = [
        'cover-01-river-folk.webp',
        'cover-02-liberation.webp',
        'cover-03-moonlit-city.webp',
        'cover-04-classical-poetry.webp',
        'cover-05-modern-stage.webp',
        'cover-06-radio-mystery.webp',
        'cover-07-science-waves.webp',
        'cover-08-morning-news.webp',
        'cover-09-living-heritage.webp',
        'cover-10-monsoon-journey.webp',
    ];

    private const ARTISTS = [
        'artist-01-folk-singer.webp',
        'artist-02-classical-singer.webp',
        'artist-03-composer.webp',
        'artist-04-lyricist.webp',
        'artist-05-presenter.webp',
    ];

    private const AUDIOBOOKS = [
        'audiobook-01-heritage-stories.webp',
        'audiobook-02-city-river.webp',
        'audiobook-03-accessible-knowledge.webp',
    ];

    private const LIVE = [
        'live-01-national-studio.webp',
        'live-02-dhaka-fm.webp',
    ];

    public function run(): void
    {
        $this->installArtworkFiles();

        $updated = [
            'songs' => $this->updateSongs(),
            'albums' => $this->updateAlbums(),
            'artist photos and covers' => $this->updateArtists(),
            'programmes' => $this->updateProgrammes(),
            'programme episodes' => $this->updateProgrammeEpisodes(),
            'podcasts' => $this->updatePodcasts(),
            'podcast episodes' => $this->updatePodcastEpisodes(),
            'audiobooks' => $this->updateCyclic(AudioBook::query()->orderBy('id')->get(), 'artwork_path', self::AUDIOBOOKS),
            'playlists' => $this->updatePlaylists(),
            'live radio' => $this->updateCyclic(BroadcastChannel::query()->audio()->orderBy('id')->get(), 'artwork_path', self::LIVE),
            'home banners' => $this->updateCyclic(Banner::query()->orderBy('position')->orderBy('id')->get(), 'image_path', self::COVERS),
        ];

        $summary = collect($updated)
            ->map(fn (int $count, string $module): string => "{$module}: {$count}")
            ->implode(', ');

        $this->command?->info("Modern Listen artwork installed ({$summary}).");
    }

    private function installArtworkFiles(): void
    {
        $sourceDirectory = base_path('database/seeders/assets/'.self::DIRECTORY);

        if (! File::isDirectory($sourceDirectory)) {
            throw new RuntimeException("Listen artwork directory is missing: {$sourceDirectory}");
        }

        $disk = Storage::disk('public');

        foreach ([...self::COVERS, ...self::ARTISTS, ...self::AUDIOBOOKS, ...self::LIVE] as $file) {
            $source = $sourceDirectory.DIRECTORY_SEPARATOR.$file;

            if (! File::isFile($source) || File::size($source) === 0) {
                throw new RuntimeException("Listen artwork source is missing or empty: {$source}");
            }

            $disk->put($this->path($file), File::get($source), 'public');
        }
    }

    private function updateSongs(): int
    {
        $count = 0;

        AudioAsset::query()
            ->whereHas('song')
            ->orderBy('id')
            ->get()
            ->each(function (AudioAsset $asset) use (&$count): void {
                if (! $this->shouldReplace($asset->artwork_path)) {
                    return;
                }

                $asset->artwork_path = $this->coverFor((string) $asset->title, (int) $asset->id);
                $asset->saveQuietly();
                $count++;
            });

        return $count;
    }

    private function updateAlbums(): int
    {
        $known = [
            'ekattorer-gaan' => 'cover-02-liberation.webp',
            'golden-melodies-of-betar' => 'cover-05-modern-stage.webp',
            'palli-geeti-collection' => 'cover-01-river-folk.webp',
            'rabindra-smarane' => 'cover-04-classical-poetry.webp',
        ];

        return $this->updateMapped(Album::query()->orderBy('id')->get(), 'artwork_path', $known, self::COVERS);
    }

    private function updateArtists(): int
    {
        $known = [
            'alam-chowdhury' => 'artist-01-folk-singer.webp',
            'ferdous-ara-begum' => 'artist-02-classical-singer.webp',
            'ustad-momtaz-ali' => 'artist-03-composer.webp',
            'jasimuddin-mondal' => 'artist-04-lyricist.webp',
            'russell-chowdhury' => 'artist-05-presenter.webp',
        ];
        $count = 0;

        Artist::query()->orderBy('id')->get()->values()->each(function (Artist $artist, int $index) use ($known, &$count): void {
            $file = $known[$artist->slug] ?? self::ARTISTS[$index % count(self::ARTISTS)];
            $path = $this->path($file);

            foreach (['photo_path', 'cover_path'] as $field) {
                if (! $this->shouldReplace($artist->{$field})) {
                    continue;
                }

                $artist->{$field} = $path;
                $count++;
            }

            if ($artist->isDirty()) {
                $artist->saveQuietly();
            }
        });

        return $count;
    }

    private function updateProgrammes(): int
    {
        $known = [
            'bhoot-fm' => 'cover-06-radio-mystery.webp',
            'durbar-sangeet' => 'cover-05-modern-stage.webp',
            'probhati-sangbad' => 'cover-08-morning-news.webp',
            'ratri-natok' => 'cover-03-moonlit-city.webp',
            'shonar-bangla-magazine' => 'cover-09-living-heritage.webp',
        ];

        return $this->updateMapped(Programme::query()->orderBy('id')->get(), 'artwork_path', $known, self::COVERS);
    }

    private function updateProgrammeEpisodes(): int
    {
        $count = 0;

        Episode::query()->with('programme')->orderBy('id')->get()->each(function (Episode $episode) use (&$count): void {
            if (! $this->shouldReplace($episode->artwork_path)) {
                return;
            }

            $episode->artwork_path = $episode->programme?->artwork_path
                ?: $this->coverFor((string) $episode->title, (int) $episode->id);
            $episode->saveQuietly();
            $count++;
        });

        return $count;
    }

    private function updatePodcasts(): int
    {
        $known = [
            'betar-itihash' => 'cover-02-liberation.webp',
            'shobder-golpo' => 'cover-09-living-heritage.webp',
            'betar-science-cafe' => 'cover-07-science-waves.webp',
        ];

        return $this->updateMapped(PodcastChannel::query()->orderBy('id')->get(), 'artwork_path', $known, self::COVERS);
    }

    private function updatePodcastEpisodes(): int
    {
        $count = 0;

        PodcastEpisode::query()->with('channel')->orderBy('id')->get()->each(function (PodcastEpisode $episode) use (&$count): void {
            if (! $this->shouldReplace($episode->artwork_path)) {
                return;
            }

            $episode->artwork_path = $episode->channel?->artwork_path
                ?: $this->coverFor((string) $episode->title, (int) $episode->id);
            $episode->saveQuietly();
            $count++;
        });

        return $count;
    }

    private function updatePlaylists(): int
    {
        $known = [
            'songs-of-1971' => 'cover-02-liberation.webp',
            'golden-age-of-radio-drama' => 'cover-06-radio-mystery.webp',
            'rainy-day-folk' => 'cover-10-monsoon-journey.webp',
        ];

        return $this->updateMapped(Playlist::query()->orderBy('id')->get(), 'artwork_path', $known, self::COVERS);
    }

    /**
     * @param  iterable<int, Model>  $models
     * @param  array<string, string>  $known
     * @param  list<string>  $fallback
     */
    private function updateMapped(iterable $models, string $field, array $known, array $fallback): int
    {
        $count = 0;

        collect($models)->values()->each(function (Model $model, int $index) use ($field, $known, $fallback, &$count): void {
            if (! $this->shouldReplace($model->{$field})) {
                return;
            }

            $slug = (string) ($model->slug ?? '');
            $model->{$field} = $this->path($known[$slug] ?? $fallback[$index % count($fallback)]);
            $model->saveQuietly();
            $count++;
        });

        return $count;
    }

    /**
     * @param  iterable<int, Model>  $models
     * @param  list<string>  $files
     */
    private function updateCyclic(iterable $models, string $field, array $files): int
    {
        $count = 0;

        collect($models)->values()->each(function (Model $model, int $index) use ($field, $files, &$count): void {
            if (! $this->shouldReplace($model->{$field})) {
                return;
            }

            $model->{$field} = $this->path($files[$index % count($files)]);
            $model->saveQuietly();
            $count++;
        });

        return $count;
    }

    private function coverFor(string $title, int $id): string
    {
        $index = abs((int) crc32(mb_strtolower($title).'#'.$id)) % count(self::COVERS);

        return $this->path(self::COVERS[$index]);
    }

    private function shouldReplace(?string $path): bool
    {
        $normalized = str_replace('\\', '/', (string) $path);

        return blank($path)
            || str_starts_with($normalized, 'demo-artwork/')
            || (str_starts_with($normalized, self::DIRECTORY.'/') && str_ends_with($normalized, '.png'));
    }

    private function path(string $file): string
    {
        return self::DIRECTORY.'/'.$file;
    }
}
