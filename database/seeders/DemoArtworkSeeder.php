<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\AudioAsset;
use App\Models\AudioBook;
use App\Models\BroadcastChannel;
use App\Models\Playlist;
use App\Models\PodcastChannel;
use App\Models\Programme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Installs the bundled demo artwork and fills image fields that are still
 * empty. User-uploaded artwork is deliberately left untouched.
 */
class DemoArtworkSeeder extends Seeder
{
    private const ARTWORK = [
        'albums' => 'demo-artwork/albums.png',
        'artists' => 'demo-artwork/artists.png',
        'audiobooks' => 'demo-artwork/audiobooks.png',
        'live-radio' => 'demo-artwork/live-radio.png',
        'playlists' => 'demo-artwork/playlists.png',
        'podcasts' => 'demo-artwork/podcasts.png',
        'programmes' => 'demo-artwork/programmes.png',
        'songs' => 'demo-artwork/songs.png',
    ];

    public function run(): void
    {
        $this->installArtworkFiles();

        $updated = [
            'albums' => $this->fillMissing(Album::query(), 'artwork_path', self::ARTWORK['albums']),
            'artist photos' => $this->fillMissing(Artist::query(), 'photo_path', self::ARTWORK['artists']),
            'artist covers' => $this->fillMissing(Artist::query(), 'cover_path', self::ARTWORK['artists']),
            'audiobooks' => $this->fillMissing(AudioBook::query(), 'artwork_path', self::ARTWORK['audiobooks']),
            'live radio' => $this->fillMissing(BroadcastChannel::query(), 'artwork_path', self::ARTWORK['live-radio']),
            'playlists' => $this->fillMissing(Playlist::query(), 'artwork_path', self::ARTWORK['playlists']),
            'podcasts' => $this->fillMissing(PodcastChannel::query(), 'artwork_path', self::ARTWORK['podcasts']),
            'programmes' => $this->fillMissing(Programme::query(), 'artwork_path', self::ARTWORK['programmes']),
            'songs' => $this->fillMissing(
                AudioAsset::query()->whereHas('song'),
                'artwork_path',
                self::ARTWORK['songs'],
            ),
        ];

        $summary = collect($updated)
            ->map(fn (int $count, string $module): string => "{$module}: {$count}")
            ->implode(', ');

        $this->command?->info("Demo artwork filled ({$summary})");
    }

    private function installArtworkFiles(): void
    {
        $disk = Storage::disk('public');

        foreach (self::ARTWORK as $file) {
            $source = base_path('database/seeders/assets/'.$file);

            if (! File::isFile($source)) {
                throw new RuntimeException("Demo artwork source is missing: {$source}");
            }

            $disk->put($file, File::get($source), 'public');
        }
    }

    private function fillMissing(Builder $query, string $column, string $path): int
    {
        return $query
            ->where(fn (Builder $missing): Builder => $missing
                ->whereNull($column)
                ->orWhere($column, ''))
            ->update([$column => $path]);
    }
}
