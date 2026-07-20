<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdAsset;
use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Models\Advertiser;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M27 — advertisers, campaigns, ad assets (incl. house/PSA fallback)
 * and impression logs.
 */
class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        $bn = Language::query()->where('code', 'bn')->value('id');

        $advertisers = [
            ['Rupali Consumer Goods Ltd.', 'Sadia Karim', 'ads@rupali.example'],
            ['Padma Telecom', 'Mizanur Rahman', 'media@padmatel.example'],
            ['Northern Bank PLC', 'Farzana Haque', 'brand@northernbank.example'],
        ];

        $advertiserModels = [];
        foreach ($advertisers as [$name, $contact, $email]) {
            $advertiserModels[$name] = Advertiser::query()->updateOrCreate(
                ['name' => $name],
                ['contact_person' => $contact, 'email' => $email],
            );
        }

        $campaigns = [
            ['Rupali Soap — Monsoon Push', 'Rupali Consumer Goods Ltd.', 'active', now()->subWeeks(2), now()->addWeeks(6), 250000, 500000, 3],
            ['Padma Telecom — Data Pack Launch', 'Padma Telecom', 'active', now()->subWeeks(1), now()->addWeeks(3), 400000, 800000, 2],
            ['Northern Bank — Savings Week', 'Northern Bank PLC', 'pending_approval', now()->addWeeks(1), now()->addWeeks(5), 150000, 300000, 5],
        ];

        $campaignModels = [];
        foreach ($campaigns as [$name, $advertiserName, $status, $start, $end, $budget, $goal, $priority]) {
            $campaignModels[$name] = AdCampaign::query()->updateOrCreate(
                ['name' => $name],
                [
                    'advertiser_id' => $advertiserModels[$advertiserName]->id,
                    'status' => $status,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'budget' => $budget,
                    'impression_goal' => $goal,
                    'priority' => $priority,
                    'targeting' => [
                        'categories' => ['songs', 'radio-programmes'],
                        'time_of_day' => ['morning', 'evening'],
                        'platforms' => ['web', 'android', 'ios'],
                    ],
                    'frequency_cap_per_hour' => 4,
                ],
            );
        }

        $adAssets = [
            ['Rupali Soap 30s Spot', 'commercial', 'Rupali Soap — Monsoon Push', 30, 'active'],
            ['Padma 4G 20s Spot', 'commercial', 'Padma Telecom — Data Pack Launch', 20, 'active'],
            ['Northern Bank 30s Spot', 'commercial', 'Northern Bank — Savings Week', 30, 'pending_approval'],
            ['Betar Station ID — Dhaka', 'house', null, 10, 'active'],
            ['Flood Preparedness PSA', 'psa', null, 45, 'active'],
            ['Vaccination Drive PSA', 'psa', null, 30, 'active'],
        ];

        $assetModels = [];
        foreach ($adAssets as [$title, $type, $campaignName, $duration, $status]) {
            $assetModels[$title] = AdAsset::query()->updateOrCreate(
                ['title' => $title],
                [
                    'ad_campaign_id' => $campaignName ? $campaignModels[$campaignName]->id : null,
                    'ad_type' => $type,
                    'file_path' => 'ads/'.\Illuminate\Support\Str::slug($title).'.mp3',
                    'duration_seconds' => $duration,
                    'language_id' => $bn,
                    'valid_from' => now()->subMonth()->toDateString(),
                    'valid_until' => now()->addMonths(3)->toDateString(),
                    'status' => $status,
                ],
            );
        }

        // Impression logs for the active commercial spots (FR-ADV-06).
        $listeners = User::query()->where('user_type', 'listener')->pluck('id');
        foreach (['Rupali Soap 30s Spot', 'Padma 4G 20s Spot', 'Betar Station ID — Dhaka'] as $title) {
            $asset = $assetModels[$title];
            for ($i = 0; $i < 60; $i++) {
                AdImpression::query()->create([
                    'ad_asset_id' => $asset->id,
                    'ad_campaign_id' => $asset->ad_campaign_id,
                    'user_id' => $listeners->isNotEmpty() && $i % 3 !== 0 ? $listeners->random() : null,
                    'anonymous_id' => $i % 3 === 0 ? 'anon-'.md5((string) $i) : null,
                    'slot' => $i % 4 === 0 ? 'pre_roll' : 'between',
                    'platform' => ['web', 'android', 'ios'][$i % 3],
                    'completed' => $i % 5 !== 0,
                    'created_at' => now()->subDays(random_int(0, 14))->subMinutes(random_int(0, 1440)),
                ]);
            }
        }

        $this->command?->info('Advertisements: advertisers, campaigns, assets + 180 impressions seeded');
    }
}
