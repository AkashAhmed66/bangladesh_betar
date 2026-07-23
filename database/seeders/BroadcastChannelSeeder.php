<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BroadcastChannel;
use App\Models\Station;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            ],
            [
                'name' => 'Dhaka FM Live',
                'name_bn' => 'ঢাকা এফএম লাইভ',
                'slug' => 'dhaka-fm-live',
                'description' => 'Live music and talk from the Dhaka studios.',
            ],
        ];

        foreach ($channels as $data) {
            BroadcastChannel::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'name_bn' => $data['name_bn'],
                    'description' => $data['description'],
                    'station_id' => $stationId,
                    'room_name' => 'betar-'.$data['slug'],
                    'is_active' => true,
                ],
            );
        }

        $this->command?->info('Broadcast channels: '.count($channels));
    }
}
