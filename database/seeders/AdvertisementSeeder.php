<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Models\Advertiser;
use App\Models\AudioAsset;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * M27 — advertisers, campaigns (whose creative is an existing audio asset)
 * and impression logs.
 */
class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
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

        // A campaign's creative is now an existing audio asset. Use a handful of
        // published assets as stand-in ad spots so serving works out of the box.
        $creatives = AudioAsset::query()->where('status', 'published')->orderBy('id')->take(3)->pluck('id')->all();

        $campaigns = [
            ['Rupali Soap — Monsoon Push', 'Rupali Consumer Goods Ltd.', 'active', now()->subWeeks(2), now()->addWeeks(6), 250000, 500000, 3],
            ['Padma Telecom — Data Pack Launch', 'Padma Telecom', 'active', now()->subWeeks(1), now()->addWeeks(3), 400000, 800000, 2],
            ['Northern Bank — Savings Week', 'Northern Bank PLC', 'pending_approval', now()->addWeeks(1), now()->addWeeks(5), 150000, 300000, 5],
        ];

        $campaignModels = [];
        foreach ($campaigns as $i => [$name, $advertiserName, $status, $start, $end, $budget, $goal, $priority]) {
            $campaignModels[$name] = AdCampaign::query()->updateOrCreate(
                ['name' => $name],
                [
                    'advertiser_id' => $advertiserModels[$advertiserName]->id,
                    'audio_asset_id' => $creatives[$i] ?? ($creatives[0] ?? null),
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

        // Impression logs for the active campaigns (FR-ADV-06).
        $listeners = User::query()->where('user_type', 'listener')->pluck('id');
        foreach (['Rupali Soap — Monsoon Push', 'Padma Telecom — Data Pack Launch'] as $name) {
            $campaign = $campaignModels[$name];
            for ($i = 0; $i < 15; $i++) {
                AdImpression::query()->create([
                    'ad_campaign_id' => $campaign->id,
                    'audio_asset_id' => $campaign->audio_asset_id,
                    'user_id' => $listeners->isNotEmpty() && $i % 3 !== 0 ? $listeners->random() : null,
                    'anonymous_id' => $i % 3 === 0 ? 'anon-'.md5((string) $i) : null,
                    'slot' => $i % 4 === 0 ? 'pre_roll' : 'between',
                    'platform' => ['web', 'android', 'ios'][$i % 3],
                    'completed' => $i % 5 !== 0,
                    'created_at' => now()->subDays(random_int(0, 14))->subMinutes(random_int(0, 1440)),
                ]);
            }
        }

        $this->command?->info('Advertisements: advertisers, campaigns + impressions seeded');
    }
}
