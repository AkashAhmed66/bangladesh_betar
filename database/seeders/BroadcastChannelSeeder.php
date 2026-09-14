<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BroadcastChannel;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * M27 â€” a couple of ready-to-use live broadcast channels. Idempotent (keyed by
 * slug). Channels start offline; a broadcaster opens the studio to go live.
 */
class BroadcastChannelSeeder extends Seeder
{
    public function run(): void
    {
        $stationId = Station::query()->orderBy('id')->value('id');

        $channels = [
            [
                'name' => 'Betar Live National',
                'name_bn' => 'à¦¬à§‡à¦¤à¦¾à¦° à¦²à¦¾à¦‡à¦­ à¦œà¦¾à¦¤à§€à¦¯à¦¼',
                'slug' => 'betar-live-national',
                'description' => 'Live national radio broadcast from Bangladesh Betar.',
                'channel_type' => 'audio',
            ],
            [
                'name' => 'Dhaka FM Live',
                'name_bn' => 'à¦¢à¦¾à¦•à¦¾ à¦à¦«à¦à¦® à¦²à¦¾à¦‡à¦­',
                'slug' => 'dhaka-fm-live',
                'description' => 'Live music and talk from the Dhaka studios.',
                'channel_type' => 'audio',
            ],
            [
                'name' => 'Betar Watch Live',
                'name_bn' => 'à¦¬à¦¾à¦‚à¦²à¦¾à¦¦à§‡à¦¶ à¦¬à§‡à¦¤à¦¾à¦° à¦¸à¦°à¦¾à¦¸à¦°à¦¿',
                'slug' => 'betar-watch-live',
                'description' => 'Live video from Bangladesh Betar studios, national events and special programmes.',
                'channel_type' => 'video',
            ],
            [
                'name' => 'Chattogram Cultural Live',
                'name_bn' => 'চট্টগ্রাম সাংস্কৃতিক লাইভ',
                'slug' => 'chattogram-cultural-live',
                'description' => 'Cultural performances and regional programmes from Chattogram.',
                'channel_type' => 'video',
                'station_code' => 'BBC',
            ],
            [
                'name' => 'Newsroom 24 Live',
                'name_bn' => 'নিউজরুম ২৪ লাইভ',
                'slug' => 'newsroom-24-live',
                'description' => 'Continuous news and public service coverage from the Betar newsroom.',
                'channel_type' => 'video',
                'station_code' => 'BBR',
            ],
        ];

        foreach ($channels as $data) {
            $channel = BroadcastChannel::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'name_bn' => $data['name_bn'],
                    'description' => $data['description'],
                    'channel_type' => $data['channel_type'],
                    'station_id' => Station::query()->where('code', $data['station_code'] ?? '')->value('id') ?? $stationId,
                    'room_name' => 'betar-'.$data['slug'],
                    'is_active' => true,
                ],
            );

            // Keep a useful poster on the seeded video channel without
            // replacing artwork uploaded by an administrator.
            if ($data['channel_type'] === 'video' && blank($channel->artwork_path)) {
                $channel->updateQuietly(['artwork_path' => 'portal/demo/watch-studio.png']);
            }
        }

        $this->command?->info('Broadcast channels: '.count($channels));
    }
}

