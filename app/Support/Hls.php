<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AudioAsset;
use App\Models\AudioBook;
use App\Models\AudioVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Encrypted-HLS packaging + URL helpers (download protection).
 *
 * Playable recordings are repackaged once into AES-128-encrypted HLS under
 * storage/app/hls/{group}/{id}/{variant}/ — players then stream small
 * encrypted .ts chunks via short-lived signed URLs instead of one saveable
 * MP3, so the Network tab / extensions / curl never see a usable audio file.
 * The legacy direct routes remain only as a fallback while a recording is
 * not yet packaged.
 *
 * Groups: 'version'  → audio_versions.id, variant 'main'
 *         'audiobook'→ audio_books.id,   variant male|female|enhanced
 */
final class Hls
{
    public const GROUPS = ['version', 'audiobook'];

    public static function dir(string $group, int|string $id, string $variant): string
    {
        return "hls/{$group}/{$id}/{$variant}";
    }

    public static function isPackaged(string $group, int|string $id, string $variant): bool
    {
        return Storage::disk('local')->exists(self::dir($group, $id, $variant).'/index.m3u8');
    }

    /** Signed playlist URL — the single entry point players are handed. */
    public static function playlistUrl(string $group, int|string $id, string $variant, int $ttlMinutes = 30): string
    {
        return URL::temporarySignedRoute('api.v1.hls.playlist', now()->addMinutes($ttlMinutes), [
            'group' => $group, 'id' => $id, 'variant' => $variant,
        ]);
    }

    /** Queue packaging exactly once (10-minute dedup guard). */
    public static function ensureQueued(string $group, int|string $id, string $variant): void
    {
        if (Cache::add("hls-package:{$group}:{$id}:{$variant}", 1, 600)) {
            \App\Jobs\PackageHlsAudio::dispatch($group, (int) $id, $variant);
        }
    }

    /**
     * The file a version's players actually serve — mirrors the demo-track
     * fallback used by the streaming endpoints so packaging matches playback.
     */
    public static function sourceForVersion(?AudioVersion $version, int $assetId): ?string
    {
        $disk = Storage::disk('local');
        $relative = $version?->file_path && $disk->exists($version->file_path)
            ? $version->file_path
            : sprintf('demo-audio/track-%02d.wav', ($assetId % 12) + 1);

        return $disk->exists($relative) ? $disk->path($relative) : null;
    }

    /**
     * Admin players: signed playlist URL when packaged; otherwise queue the
     * packaging and return null so the view falls back to the direct route.
     */
    public static function adminAssetHls(AudioAsset $asset, ?AudioVersion $version): ?string
    {
        if ($version === null || $version->version_type === 'preservation_master') {
            return null;
        }
        if (self::isPackaged('version', $version->id, 'main')) {
            return self::playlistUrl('version', $version->id, 'main');
        }
        if (self::sourceForVersion($version, $asset->id) !== null) {
            self::ensureQueued('version', $version->id, 'main');
        }

        return null;
    }

    public static function adminBookHls(AudioBook $book, string $voice): ?string
    {
        $path = match ($voice) {
            'male' => $book->audio_male_path,
            'female' => $book->audio_female_path,
            'enhanced' => $book->audio_enhanced_path,
            default => null,
        };
        if (! $path) {
            return null;
        }
        if (self::isPackaged('audiobook', $book->id, $voice)) {
            return self::playlistUrl('audiobook', $book->id, $voice);
        }
        if (Storage::disk('local')->exists($path)) {
            self::ensureQueued('audiobook', $book->id, $voice);
        }

        return null;
    }

    /**
     * Package one audio file into AES-128 encrypted HLS. Builds into a temp
     * dir and renames into place so a half-written package is never served.
     * Pure remux for MP3 sources; AAC encode otherwise — cheap either way.
     */
    public static function package(string $sourceAbs, string $group, int|string $id, string $variant): bool
    {
        if (! is_file($sourceAbs)) {
            return false;
        }

        $disk = Storage::disk('local');
        $tmpAbs = $disk->path('hls/.tmp/'.uniqid("{$group}-{$id}-{$variant}-", true));
        if (! @mkdir($tmpAbs, 0775, true)) {
            return false;
        }

        try {
            file_put_contents("{$tmpAbs}/key.bin", random_bytes(16));
            // ffmpeg reads the key from line 2 at package time; line 1 is the
            // URI written into the playlist — a placeholder we rewrite into a
            // signed key URL on every playlist request.
            file_put_contents("{$tmpAbs}/keyinfo.txt", "__KEY_URI__\n{$tmpAbs}/key.bin\n");

            $codec = strtolower(pathinfo($sourceAbs, PATHINFO_EXTENSION)) === 'mp3'
                ? '-c:a copy'
                : '-c:a aac -b:a 128k';

            exec(sprintf(
                'ffmpeg -y -v error -i %s -vn -map 0:a:0 %s -f hls -hls_time 10 -hls_list_size 0 '
                .'-hls_playlist_type vod -hls_key_info_file %s -hls_segment_filename %s %s 2>&1',
                escapeshellarg($sourceAbs), $codec,
                escapeshellarg("{$tmpAbs}/keyinfo.txt"),
                escapeshellarg("{$tmpAbs}/seg%04d.ts"),
                escapeshellarg("{$tmpAbs}/index.m3u8"),
            ), $out, $exit);
            @unlink("{$tmpAbs}/keyinfo.txt");

            $playlist = @file_get_contents("{$tmpAbs}/index.m3u8") ?: '';
            if ($exit !== 0 || ! str_contains($playlist, '#EXT-X-ENDLIST')) {
                Log::warning('[hls] packaging failed', ['group' => $group, 'id' => $id, 'variant' => $variant, 'exit' => $exit, 'out' => implode(' ', array_slice($out, 0, 5))]);
                self::rrmdir($tmpAbs);

                return false;
            }

            $finalAbs = $disk->path(self::dir($group, $id, $variant));
            self::rrmdir($finalAbs);
            @mkdir(dirname($finalAbs), 0775, true);

            return rename($tmpAbs, $finalAbs);
        } catch (\Throwable $e) {
            Log::warning('[hls] packaging error', ['group' => $group, 'id' => $id, 'variant' => $variant, 'error' => $e->getMessage()]);
            self::rrmdir($tmpAbs);

            return false;
        }
    }

    /** Remove a package (variant) or every variant for an id. */
    public static function delete(string $group, int|string $id, ?string $variant = null): void
    {
        $disk = Storage::disk('local');
        $rel = $variant === null ? "hls/{$group}/{$id}" : self::dir($group, $id, $variant);
        self::rrmdir($disk->path($rel));
    }

    private static function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = "{$dir}/{$entry}";
            is_dir($path) ? self::rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
