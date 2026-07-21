<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Mood;
use App\Models\Station;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Stations, departments and controlled vocabularies (M04/M05).
 */
class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $stations = [
            ['Bangladesh Betar Dhaka', 'বাংলাদেশ বেতার ঢাকা', 'BBD', 'Dhaka', '693 kHz / 104.0 FM'],
            ['Bangladesh Betar Chattogram', 'বাংলাদেশ বেতার চট্টগ্রাম', 'BBC', 'Chattogram', '873 kHz'],
            ['Bangladesh Betar Khulna', 'বাংলাদেশ বেতার খুলনা', 'BBK', 'Khulna', '558 kHz'],
            ['Bangladesh Betar Rajshahi', 'বাংলাদেশ বেতার রাজশাহী', 'BBR', 'Rajshahi', '846 kHz'],
            ['Bangladesh Betar Sylhet', 'বাংলাদেশ বেতার সিলেট', 'BBS', 'Sylhet', '963 kHz'],
        ];

        foreach ($stations as [$name, $nameBn, $code, $location, $frequency]) {
            $station = Station::query()->updateOrCreate(
                ['code' => $code],
                compact('name') + ['name_bn' => $nameBn, 'location' => $location, 'frequency' => $frequency],
            );

            foreach ([
                ['Music', 'সঙ্গীত', 'MUS'],
                ['News & Current Affairs', 'সংবাদ', 'NEWS'],
                ['Drama & Entertainment', 'নাটক', 'DRAMA'],
                ['Programme Production', 'অনুষ্ঠান', 'PROG'],
            ] as [$dName, $dNameBn, $dCode]) {
                Department::query()->updateOrCreate(
                    ['station_id' => $station->id, 'code' => $dCode],
                    ['name' => $dName, 'name_bn' => $dNameBn],
                );
            }
        }

        $categories = [
            ['Songs', 'গান', 'content'],
            ['Radio Programmes', 'বেতার অনুষ্ঠান', 'content'],
            ['News', 'সংবাদ', 'content'],
            ['Drama', 'নাটক', 'content'],
            ['Historical Recordings', 'ঐতিহাসিক রেকর্ডিং', 'content'],
        ];

        foreach ($categories as [$name, $nameBn, $type]) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'name_bn' => $nameBn, 'type' => $type],
            );
        }

        $genres = [
            ['Modern Bangla', 'আধুনিক বাংলা'], ['Folk', 'লোকগীতি'], ['Rabindra Sangeet', 'রবীন্দ্রসঙ্গীত'],
            ['Nazrul Sangeet', 'নজরুলসঙ্গীত'], ['Patriotic', 'দেশাত্মবোধক'],
        ];

        foreach ($genres as [$name, $nameBn]) {
            Genre::query()->updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'name_bn' => $nameBn]);
        }

        $moods = [
            ['Joyful', 'আনন্দময়'], ['Energetic', 'প্রাণবন্ত'], ['Calm', 'শান্ত'],
            ['Inspirational', 'অনুপ্রেরণামূলক'], ['Nostalgic', 'স্মৃতিমধুর'],
        ];

        foreach ($moods as [$name, $nameBn]) {
            Mood::query()->updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'name_bn' => $nameBn]);
        }

        $languages = [
            ['Bangla', 'বাংলা', 'bn'], ['English', 'ইংরেজি', 'en'], ['Chattogram Dialect', 'চাটগাঁইয়া', 'bn-ctg'],
            ['Sylheti', 'সিলেটি', 'bn-syl'], ['Urdu', 'উর্দু', 'ur'],
        ];

        foreach ($languages as [$name, $nameBn, $code]) {
            Language::query()->updateOrCreate(['code' => $code], ['name' => $name, 'name_bn' => $nameBn]);
        }

        $tags = [
            'Liberation War', '1971', 'Victory Day', 'Golden Era', 'Rare Recording',
        ];

        foreach ($tags as $tag) {
            Tag::query()->updateOrCreate(['slug' => Str::slug($tag)], ['name' => $tag]);
        }

        $this->command?->info('Taxonomy: stations, departments, categories, genres, moods, languages, tags seeded');
    }
}
