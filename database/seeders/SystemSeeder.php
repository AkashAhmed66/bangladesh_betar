<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AudioAsset;
use App\Models\BackupRun;
use App\Models\IntegrityCheck;
use Illuminate\Database\Seeder;

/**
 * M22 — backup runs and fixity (integrity) checks.
 */
class SystemSeeder extends Seeder
{
    public function run(): void
    {
        for ($day = 1; $day <= 10; $day++) {
            foreach ([['database', 'local'], ['audio', 'local'], ['full', 'offsite']] as [$type, $target]) {
                if ($type === 'full' && $day % 7 !== 0) {
                    continue; // weekly off-site full backup
                }
                BackupRun::query()->firstOrCreate(
                    [
                        'backup_type' => $type,
                        'target' => $target,
                        'started_at' => now()->subDays($day)->setTime(2, 0),
                    ],
                    [
                        'status' => $day === 4 && $type === 'audio' ? 'failed' : 'success',
                        'finished_at' => now()->subDays($day)->setTime(2, random_int(20, 55)),
                        'size_bytes' => random_int(2, 40) * 1024 ** 3,
                        'notes' => $day === 4 && $type === 'audio' ? 'Target volume unreachable — retried next cycle.' : null,
                    ],
                );
            }
        }

        foreach (AudioAsset::query()->inRandomOrder()->take(12)->get() as $index => $asset) {
            IntegrityCheck::query()->firstOrCreate(
                ['audio_asset_id' => $asset->id],
                [
                    'result' => $index === 0 ? 'corrupt' : 'ok',
                    'details' => $index === 0 ? 'Checksum mismatch on local backup copy; restore from off-site scheduled.' : 'All three copies verified.',
                    'checked_at' => now()->subDays(random_int(1, 30)),
                ],
            );
        }

        $this->command?->info('System: backup runs + integrity checks seeded');
    }
}
