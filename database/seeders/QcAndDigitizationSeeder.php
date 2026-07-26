<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AudioAsset;
use App\Models\MediaItem;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M03 — digitization register (physical media items).
 */
class QcAndDigitizationSeeder extends Seeder
{
    public function run(): void
    {
        $archivist = User::query()->where('email', 'archivist@betar.gov.bd')->first();
        $admin = User::query()->where('email', 'archive.admin@betar.gov.bd')->first();
        $dhaka = Station::query()->where('code', 'BBD')->first();

        // ---- Digitization register (physical media items) ----
        $items = [
            ['REEL-1971-0001', 'Liberation War Field Recordings — Reel 1', 'reel', 'critical', 'Vault A / Shelf 3', 'high', 'archived'],
            ['REEL-1971-0002', 'Liberation War Field Recordings — Reel 2', 'reel', 'poor', 'Vault A / Shelf 3', 'high', 'restored'],
            ['CAS-1985-0117', 'Golden Melodies Session Tapes', 'cassette', 'fair', 'Vault B / Shelf 12', 'medium', 'captured'],
            ['DAT-1994-0033', 'Drama Unit Master DAT — Kobor', 'dat', 'good', 'Vault B / Shelf 5', 'medium', 'in_progress'],
            ['REEL-1975-0044', 'Historic Speeches Compilation Reel', 'reel', 'poor', 'Vault A / Shelf 7', 'high', 'qc_pending'],
        ];

        $digitizedAssets = AudioAsset::query()->where('source', 'digitization')->pluck('id');
        foreach ($items as $index => [$code, $title, $type, $condition, $location, $priority, $status]) {
            MediaItem::query()->updateOrCreate(
                ['item_code' => $code],
                [
                    'title' => $title,
                    'media_type' => $type,
                    'condition' => $condition,
                    'location' => $location,
                    'priority' => $priority,
                    'status' => $status,
                    'station_id' => $dhaka?->id,
                    'audio_asset_id' => in_array($status, ['archived', 'restored'], true) ? $digitizedAssets->get($index % max(1, $digitizedAssets->count())) : null,
                    'registered_by' => $admin?->id,
                    'digitized_by' => in_array($status, ['archived', 'restored', 'captured'], true) ? $archivist?->id : null,
                    'digitized_at' => in_array($status, ['archived', 'restored', 'captured'], true) ? now()->subDays(random_int(10, 200)) : null,
                    'restoration_notes' => $status === 'restored' ? 'Hiss reduction and click removal applied; raw capture retained.' : null,
                ],
            );
        }

        $this->command?->info('Digitization: '.count($items).' media items seeded');
    }
}
