<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\AudioAsset;
use App\Models\AudioVersion;
use App\Models\Language;
use App\Models\Station;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Shared helper for seeders that need published audio assets with
 * a preservation master + online + preview version family (M02/M04).
 */
trait CreatesAudioAssets
{
    protected function makeAsset(string $title, array $overrides = []): AudioAsset
    {
        $slug = Str::slug($title).'-'.Str::lower(Str::random(4));
        $duration = $overrides['duration_seconds'] ?? random_int(120, 3600);
        $uploader = User::query()->where('email', 'archivist@betar.gov.bd')->first();

        $asset = AudioAsset::query()->create(array_merge([
            'archive_no' => AudioAsset::nextArchiveNo(),
            'title' => $title,
            'slug' => $slug,
            'content_type' => 'programme',
            'station_id' => Station::query()->inRandomOrder()->value('id'),
            'language_id' => Language::query()->where('code', 'bn')->value('id'),
            'uploaded_by' => $uploader?->id,
            'source' => 'upload',
            'original_filename' => Str::slug($title).'.wav',
            'duration_seconds' => $duration,
            'format' => 'wav',
            'sample_rate' => 48000,
            'bit_depth' => 24,
            'channels' => 2,
            'bitrate_kbps' => 2304,
            'loudness_lufs' => round(-23 + mt_rand(-30, 30) / 10, 2),
            'peak_db' => round(-3 - mt_rand(0, 60) / 10, 2),
            'silence_percent' => round(mt_rand(0, 80) / 10, 2),
            'size_bytes' => $duration * 288000,
            'checksum_sha256' => hash('sha256', $slug),
            'waveform_peaks' => self::waveformPeaks(),
            'status' => 'published',
            'access_level' => 'public',
            'rights_status' => 'cleared',
            'recorded_on' => now()->subDays(random_int(60, 4000))->toDateString(),
            'first_broadcast_on' => now()->subDays(random_int(30, 3650))->toDateString(),
            'published_at' => now()->subDays(random_int(1, 900)),
            'play_count' => random_int(500, 250000),
            'favorite_count' => random_int(10, 8000),
            'avg_rating' => round(mt_rand(30, 50) / 10, 2),
            'rating_count' => random_int(5, 900),
        ], $overrides));

        $master = AudioVersion::query()->create([
            'audio_asset_id' => $asset->id,
            'version_type' => 'preservation_master',
            'label' => 'Preservation Master (WAV 48k/24bit)',
            'file_path' => "masters/{$asset->archive_no}.wav",
            'format' => 'wav',
            'bitrate_kbps' => 2304,
            'duration_seconds' => $asset->duration_seconds,
            'size_bytes' => $asset->size_bytes,
            'checksum_sha256' => $asset->checksum_sha256,
            'is_default' => false,
            'created_by' => $asset->uploaded_by,
        ]);

        AudioVersion::query()->create([
            'audio_asset_id' => $asset->id,
            'version_type' => 'online',
            'label' => 'Online Streaming (AAC 128k)',
            'file_path' => "streams/{$asset->archive_no}-128.m4a",
            'format' => 'aac',
            'bitrate_kbps' => 128,
            'duration_seconds' => $asset->duration_seconds,
            'size_bytes' => (int) ($asset->duration_seconds * 16000),
            'checksum_sha256' => hash('sha256', $asset->slug.'-online'),
            'is_default' => true,
            'derived_from_id' => $master->id,
            'created_by' => $asset->uploaded_by,
        ]);

        AudioVersion::query()->create([
            'audio_asset_id' => $asset->id,
            'version_type' => 'preview',
            'label' => 'Preview Clip (90s MP3)',
            'file_path' => "previews/{$asset->archive_no}-preview.mp3",
            'format' => 'mp3',
            'bitrate_kbps' => 96,
            'duration_seconds' => min(90, $asset->duration_seconds),
            'size_bytes' => 90 * 12000,
            'checksum_sha256' => hash('sha256', $asset->slug.'-preview'),
            'is_default' => false,
            'derived_from_id' => $master->id,
            'created_by' => $asset->uploaded_by,
        ]);

        return $asset;
    }

    /** Pre-computed waveform amplitude buckets (FR-PLY-16). */
    protected static function waveformPeaks(int $buckets = 160): array
    {
        $peaks = [];
        $phase = mt_rand(0, 628) / 100;
        for ($i = 0; $i < $buckets; $i++) {
            $base = 0.35 + 0.3 * abs(sin($i / 9 + $phase)) + 0.2 * abs(sin($i / 3.7));
            $peaks[] = round(min(1, max(0.05, $base + mt_rand(-18, 18) / 100)), 3);
        }

        return $peaks;
    }
}
