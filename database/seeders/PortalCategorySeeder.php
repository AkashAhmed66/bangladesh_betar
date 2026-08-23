<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NewsCategory;
use App\Models\WatchCategory;
use Illuminate\Database\Seeder;

final class PortalCategorySeeder extends Seeder
{
    public function run(): void
    {
        $news = [
            ['Bangladesh', 'বাংলাদেশ', 'bangladesh'], ['Economy', 'অর্থনীতি', 'economy'],
            ['Climate', 'জলবায়ু', 'climate'], ['Culture', 'সংস্কৃতি', 'culture'],
            ['Science', 'বিজ্ঞান', 'science'], ['Environment', 'পরিবেশ', 'environment'],
            ['Media', 'গণমাধ্যম', 'media'],
        ];
        $watch = [
            ['Live TV', 'সরাসরি টিভি', 'live-tv'], ['Drama', 'নাটক', 'drama'],
            ['Documentary', 'প্রামাণ্যচিত্র', 'documentary'], ['Culture', 'সংস্কৃতি ও সংগীত', 'culture'],
            ['Kids', 'শিশু', 'kids'],
        ];

        foreach ($news as $position => [$name, $nameBn, $slug]) {
            NewsCategory::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'name_bn' => $nameBn, 'position' => $position, 'is_active' => true,
                'show_in_header' => in_array($slug, ['bangladesh', 'economy', 'climate'], true),
            ]);
        }
        foreach ($watch as $position => [$name, $nameBn, $slug]) {
            WatchCategory::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'name_bn' => $nameBn, 'position' => $position, 'is_active' => true,
                'show_in_header' => in_array($slug, ['live-tv', 'drama', 'documentary'], true),
            ]);
        }

        $this->command?->info('News and Watch portal categories seeded.');
    }
}
