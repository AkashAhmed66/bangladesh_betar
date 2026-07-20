<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AudioAsset;
use App\Models\AudioMarker;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo AI-detected content markers + chapters for the Audio Studio
 * (Audio Visualization spec — AI-Based Content Markers). All flagged
 * is_ai=true (draft until a human confirms, FR-AIF-06).
 */
class AudioMarkerSeeder extends Seeder
{
    public function run(): void
    {
        $archivist = User::query()->where('email', 'archivist@betar.gov.bd')->first();

        $assets = AudioAsset::query()
            ->whereIn('content_type', ['speech', 'interview', 'historical', 'podcast', 'drama', 'story', 'song'])
            ->orderBy('id')->take(10)->get();

        foreach ($assets as $asset) {
            if ($asset->markers()->exists() || $asset->duration_seconds < 30) {
                continue;
            }

            $d = $asset->duration_seconds;
            $markers = [
                ['intro', 'Introduction', 0, min(15, $d * 0.05)],
                ['music', 'Signature tune', min(15, $d * 0.05), min(30, $d * 0.1)],
                ['speech', 'Main segment', $d * 0.12, $d * 0.6],
                ['keyword', 'Liberation War', $d * 0.3, null],
                ['applause', 'Applause', $d * 0.62, $d * 0.64],
                ['emotion', 'Emotional peak', $d * 0.7, null],
                ['outro', 'Closing', $d * 0.9, $d],
            ];

            // Podcast/drama get chapter markers.
            if (in_array($asset->content_type, ['podcast', 'drama', 'story'], true)) {
                $markers[] = ['chapter', 'Chapter 1', 0, null];
                $markers[] = ['chapter', 'Chapter 2', $d * 0.4, null];
                $markers[] = ['chapter', 'Chapter 3', $d * 0.75, null];
            }

            foreach ($markers as [$type, $label, $start, $end]) {
                AudioMarker::query()->create([
                    'audio_asset_id' => $asset->id,
                    'marker_type' => $type,
                    'label' => $label,
                    'start_seconds' => round($start, 2),
                    'end_seconds' => $end !== null ? round($end, 2) : null,
                    'is_ai' => true,
                    'created_by' => $archivist?->id,
                ]);
            }
        }

        $this->command?->info('Audio markers: AI content markers + chapters seeded for '.$assets->count().' assets');
    }
}
