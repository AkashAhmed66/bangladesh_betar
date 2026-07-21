<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Programme;
use App\Models\Season;
use App\Models\Station;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * M04 — Programme/Collection hierarchy with seasons.
 */
class ProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        $dhaka = Station::query()->where('code', 'BBD')->first();
        $ctg = Station::query()->where('code', 'BBC')->first();

        $programmes = [
            // [title, title_bn, type, category-slug, station, seasons]
            ['Bhoot FM', 'ভূত এফএম', 'event', null, $dhaka, [2023, 2024, 2025]],
            ['Durbar Sangeet', 'দুর্বার সঙ্গীত', 'programme', 'songs', $dhaka, [2024, 2025]],
            ['Shonar Bangla Magazine', 'সোনার বাংলা ম্যাগাজিন', 'magazine', 'radio-programmes', $dhaka, [2024, 2025]],
            ['Ratri Natok', 'রাত্রি নাটক', 'drama', 'drama', $dhaka, [2023, 2024]],
            ['Probhati Sangbad', 'প্রভাতী সংবাদ', 'news', 'news', $dhaka, [2025]],
        ];

        foreach ($programmes as [$title, $titleBn, $type, $categorySlug, $station, $seasonYears]) {
            $programme = Programme::query()->updateOrCreate(
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

            foreach ($seasonYears as $index => $year) {
                Season::query()->updateOrCreate(
                    ['programme_id' => $programme->id, 'number' => $index + 1],
                    ['title' => "Season ".($index + 1), 'year' => $year],
                );
            }
        }

        $this->command?->info('Programmes: '.count($programmes).' with seasons seeded');
    }
}
