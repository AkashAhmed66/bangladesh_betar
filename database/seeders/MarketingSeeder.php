<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\CampaignAsset;
use App\Models\MarketingCampaign;
use App\Models\Script;
use App\Models\User;
use App\Models\VoiceArtist;
use Illuminate\Database\Seeder;

/**
 * M11 — voice-artist profiles, scripts, campaigns and takes.
 */
class MarketingSeeder extends Seeder
{
    public function run(): void
    {
        $marketing = User::query()->where('email', 'marketing@betar.gov.bd')->first();

        $voices = [
            ['Arman Kabir', 'male', 'adult', 'Bangla, English', 'Standard Dhaka', 'authoritative', 'commercial'],
            ['Dilruba Yasmin', 'female', 'adult', 'Bangla', 'Standard Dhaka', 'warm', 'narration'],
            ['Habib Noor', 'male', 'senior', 'Bangla', 'Chattogram', 'gravelly', 'documentary'],
        ];

        foreach ($voices as [$name, $gender, $age, $languages, $accent, $tone, $style]) {
            VoiceArtist::query()->updateOrCreate(
                ['name' => $name],
                [
                    'artist_id' => Artist::query()->where('name', $name)->value('id'),
                    'gender' => $gender,
                    'age_range' => $age,
                    'languages' => $languages,
                    'accent' => $accent,
                    'tone' => $tone,
                    'style' => $style,
                    'sample_path' => 'voice-samples/'.\Illuminate\Support\Str::slug($name).'.mp3',
                    'is_available' => true,
                ],
            );
        }

        $scriptV1 = Script::query()->updateOrCreate(
            ['title' => 'National Tree Plantation Campaign — Radio Spot', 'version_number' => 1],
            ['body' => "30-second radio spot.\n\n[Warm voice] Ekti gach, ekti pran...", 'status' => 'archived', 'created_by' => $marketing?->id],
        );
        $scriptV2 = Script::query()->updateOrCreate(
            ['title' => 'National Tree Plantation Campaign — Radio Spot', 'version_number' => 2],
            ['body' => "30-second radio spot (revised).\n\n[Warm voice] Ekti gach, ekti pran. Aj-i ekti gach lagan...", 'status' => 'approved', 'parent_script_id' => $scriptV1->id, 'created_by' => $marketing?->id],
        );

        $roadScript = Script::query()->updateOrCreate(
            ['title' => 'Road Safety Week — PSA Script', 'version_number' => 1],
            ['body' => "45-second PSA.\n\n[Authoritative voice] Gati simito rakhun...", 'status' => 'approved', 'created_by' => $marketing?->id],
        );

        $campaigns = [
            ['National Tree Plantation Drive 2026', 'Ministry of Environment', 'approved', $scriptV2, now()->subMonths(1), now()->addMonths(2), now()->subMonths(1), now()->addMonths(11)],
            ['Road Safety Week 2026', 'BRTA', 'in_production', $roadScript, now()->addWeeks(2), now()->addWeeks(3), now()->addWeeks(2), now()->addMonths(6)],
            ['Winter Blanket Appeal 2025', 'Red Crescent', 'completed', null, now()->subMonths(8), now()->subMonths(6), now()->subMonths(8), now()->subDays(20)], // usage rights recently expired
        ];

        foreach ($campaigns as [$title, $client, $status, $script, $start, $end, $rightsStart, $rightsEnd]) {
            $campaign = MarketingCampaign::query()->updateOrCreate(
                ['title' => $title],
                [
                    'client_name' => $client,
                    'description' => "$title — produced by the Bangladesh Betar marketing unit for $client.",
                    'status' => $status,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'usage_rights_start' => $rightsStart->toDateString(),
                    'usage_rights_end' => $rightsEnd->toDateString(),
                    'created_by' => $marketing?->id,
                ],
            );

            foreach (['radio' => 1, 'social' => 2] as $channel => $take) {
                CampaignAsset::query()->firstOrCreate(
                    ['marketing_campaign_id' => $campaign->id, 'channel' => $channel, 'take_number' => $take],
                    [
                        'script_id' => $script?->id,
                        'voice_artist_id' => VoiceArtist::query()->inRandomOrder()->value('id'),
                        'file_path' => 'campaigns/'.\Illuminate\Support\Str::slug($title)."-$channel-take$take.wav",
                        'is_final' => $status !== 'in_production' && $take === 1,
                    ],
                );
            }
        }

        $this->command?->info('Marketing: voices, scripts, '.count($campaigns).' campaigns seeded');
    }
}
