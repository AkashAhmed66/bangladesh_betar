<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AudioAsset;
use App\Models\RightsHolder;
use App\Models\RightsRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M14 — rights holders and per-asset rights records with expiry spread.
 */
class RightsSeeder extends Seeder
{
    public function run(): void
    {
        $officer = User::query()->where('email', 'copyright@betar.gov.bd')->first();

        $holders = [
            ['Bangladesh Betar', 'organization', 'Directorate of Archives', 'archive@betar.gov.bd'],
            ['Bangla Music Rights Society', 'organization', 'Licensing Desk', 'licensing@bmrs.example'],
            ['Heirs of Ustad Momtaz Ali', 'person', 'Farhan Ali', 'farhan@example.com'],
            ['National Film Corporation', 'organization', 'Rights Cell', 'rights@nfc.example'],
        ];

        $holderModels = [];
        foreach ($holders as [$name, $type, $contact, $email]) {
            $holderModels[] = RightsHolder::query()->updateOrCreate(
                ['name' => $name],
                ['holder_type' => $type, 'contact_person' => $contact, 'email' => $email],
            );
        }

        $assets = AudioAsset::query()->orderBy('id')->get();
        foreach ($assets as $index => $asset) {
            $holder = $holderModels[$index % count($holderModels)];

            // Spread validity so some records are expiring soon (FR-CPR-06 demo).
            $validUntil = match ($index % 5) {
                0 => now()->addYears(5),
                1 => now()->addDays(25),   // expiring within 30 days
                2 => now()->addDays(80),   // expiring within 90 days
                3 => now()->addYears(2),
                default => null,           // perpetual
            };

            RightsRecord::query()->firstOrCreate(
                ['audio_asset_id' => $asset->id],
                [
                    'rights_holder_id' => $holder->id,
                    'rights_types' => ['broadcast', 'streaming'],
                    'territory' => 'Bangladesh',
                    'valid_from' => now()->subYears(2)->toDateString(),
                    'valid_until' => $validUntil?->toDateString(),
                    'royalty_required' => $index % 4 === 1,
                    'status' => $asset->rights_status === 'restricted' ? 'restricted'
                        : ($asset->rights_status === 'pending' ? 'pending' : 'cleared'),
                    'contract_path' => 'contracts/agreement-'.$asset->archive_no.'.pdf',
                    'created_by' => $officer?->id,
                ],
            );
        }

        $this->command?->info('Rights: '.count($holders).' holders + records for '.$assets->count().' assets seeded');
    }
}
