<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BroadcastChannel;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * M27 — a couple of ready-to-use live broadcast channels. Idempotent (keyed by
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
                'name_bn' => 'বেতার লাইভ জাতীয়',
                'slug' => 'betar-live-national',
                'description' => 'Live national radio broadcast from Bangladesh Betar.',
                'channel_type' => 'audio',
            ],
            [
                'name' => 'Dhaka FM Live',
                'name_bn' => 'ঢাকা এফএম লাইভ',
                'slug' => 'dhaka-fm-live',
                'description' => 'Live music and talk from the Dhaka studios.',
                'channel_type' => 'audio',
            ],
            [
                'name' => 'Betar Watch Live',
                'name_bn' => 'বাংলাদেশ বেতার সরাসরি',
                'slug' => 'betar-watch-live',
                'description' => 'Live video from Bangladesh Betar studios, national events and special programmes.',
                'channel_type' => 'video',
            ],
        ];

        foreach ($channels as $data) {
            BroadcastChannel::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'name_bn' => $data['name_bn'],
                    'description' => $data['description'],
                    'channel_type' => $data['channel_type'],
                    'station_id' => $stationId,
                    'room_name' => 'betar-'.$data['slug'],
                    'is_active' => true,
                ],
            );
        }

        $this->command?->info('Broadcast channels: '.count($channels));
    }
}
