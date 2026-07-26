<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Programme;
use App\Models\Station;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * M04 — Programme/Collection hierarchy.
 */
class ProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        $dhaka = Station::query()->where('code', 'BBD')->first();
        $ctg = Station::query()->where('code', 'BBC')->first();

        $programmes = [
            // [title, title_bn, type, category-slug, station]
            ['Bhoot FM', 'ভূত এফএম', 'event', null, $dhaka],
            ['Durbar Sangeet', 'দুর্বার সঙ্গীত', 'programme', 'songs', $dhaka],
            ['Shonar Bangla Magazine', 'সোনার বাংলা ম্যাগাজিন', 'magazine', 'radio-programmes', $dhaka],
            ['Ratri Natok', 'রাত্রি নাটক', 'drama', 'drama', $dhaka],
            ['Probhati Sangbad', 'প্রভাতী সংবাদ', 'news', 'news', $dhaka],
        ];

        foreach ($programmes as [$title, $titleBn, $type, $categorySlug, $station]) {
            Programme::query()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'title_bn' => $titleBn,
                    'programme_type' => $type,
                    'category_id' => Category::query()->where('slug', $categorySlug)->value('id'),
                    'station_id' => $station?->id,
                    'department_id' => $station?->departments()->where('code', 'PROG')->value('id'),
                    'description' => "$title is a flagship programme of Bangladesh Betar.",
                    'is_published' => true,
                ],
            );
        }

        $this->command?->info('Programmes: '.count($programmes).' seeded');
    }
}
