<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * M05/M08 — reusable person/entity records (singers, composers, presenters ...).
 * Fictional names are used for demo data.
 */
class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        $artists = [
            // [name, name_bn, type, featured]
            ['Alam Chowdhury', 'আলম চৌধুরী', 'singer', true],
            ['Ferdous Ara Begum', 'ফেরদৌস আরা বেগম', 'singer', true],
            ['Ustad Momtaz Ali', 'ওস্তাদ মমতাজ আলী', 'composer', false],
            ['Jasimuddin Mondal', 'জসীমউদ্দীন মণ্ডল', 'lyricist', false],
            ['Russell Chowdhury', 'রাসেল চৌধুরী', 'presenter', true],
        ];

        foreach ($artists as [$name, $nameBn, $type, $featured]) {
            Artist::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'name_bn' => $nameBn,
                    'artist_type' => $type,
                    'is_featured' => $featured,
                    'is_published' => true,
                    'bio' => "$name is a celebrated {$type} associated with Bangladesh Betar's golden era of broadcasting.",
                    'bio_bn' => "$nameBn বাংলাদেশ বেতারের স্বর্ণযুগের একজন প্রখ্যাত শিল্পী।",
                ],
            );
        }

        $this->command?->info('Artists: '.count($artists).' seeded');
    }
}
