<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AudioAsset;
use App\Models\MediaItem;
use App\Models\QcReport;
use App\Models\Station;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M03 — digitization register; M15 — automated QC reports.
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
            ['VNL-1968-0009', 'Palli Geeti 45rpm Pressings', 'vinyl', 'fair', 'Vault C / Drawer 2', 'low', 'registered'],
            ['CD-2001-0456', 'Eid Special 2001 Broadcast CD', 'cd', 'good', 'Vault C / Shelf 1', 'low', 'registered'],
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

        // ---- QC reports for every asset ----
        $assets = AudioAsset::query()->get();
        foreach ($assets as $asset) {
            $fail = $asset->status === 'pending_qc';
            $warning = ! $fail && $asset->id % 7 === 0;

            $checks = [
                'silence' => ['pass' => ! $fail, 'detail' => $fail ? 'Leading silence 14.2s exceeds 5s threshold' : 'OK'],
                'clipping' => ['pass' => true, 'detail' => 'No clipped samples detected'],
                'volume' => ['pass' => ! $warning, 'detail' => $warning ? 'Average level -28 LUFS below target' : 'OK'],
                'noise' => ['pass' => ! $fail, 'detail' => $fail ? 'Broadband hiss detected above -55 dB' : 'OK'],
                'channels' => ['pass' => true, 'detail' => 'Stereo balance within 1.2 dB'],
                'corruption' => ['pass' => true, 'detail' => 'File decodes cleanly end to end'],
                'loudness' => ['pass' => ! $warning, 'detail' => $warning ? 'Deviation from EBU R128 target' : 'Within EBU R128 tolerance'],
            ];

            QcReport::query()->firstOrCreate(
                ['audio_asset_id' => $asset->id],
                [
                    'audio_version_id' => $asset->versions()->where('version_type', 'preservation_master')->value('id'),
                    'checks' => $checks,
                    'overall_result' => $fail ? 'fail' : ($warning ? 'warning' : 'pass'),
                    'verdict' => $fail ? null : 'approved',
                    'reviewed_by' => $fail ? null : $admin?->id,
                    'reviewer_comments' => $fail ? null : 'Automated checks verified by reviewer.',
                    'reviewed_at' => $fail ? null : now()->subDays(random_int(1, 100)),
                ],
            );
        }

        $this->command?->info('Digitization: '.count($items).' media items; QC reports for '.$assets->count().' assets seeded');
    }
}
