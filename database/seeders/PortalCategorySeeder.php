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
            ['Bangladesh', 'বাংলাদেশ', 'bangladesh', true], ['Politics', 'রাজনীতি', 'politics', true],
            ['World', 'বিশ্ব', 'world', true], ['Business', 'বাণিজ্য', 'business', true],
            ['Sports', 'খেলা', 'sports', true], ['Entertainment', 'বিনোদন', 'entertainment', true],
            ['Jobs', 'চাকরি', 'jobs', true], ['Lifestyle', 'জীবনযাপন', 'lifestyle', true],
            ['Video', 'ভিডিও', 'video', true],
            ['Economy', 'অর্থনীতি', 'economy', false], ['Climate', 'জলবায়ু', 'climate', false],
            ['Culture', 'সংস্কৃতি', 'culture', false], ['Science', 'বিজ্ঞান', 'science', false],
            ['Environment', 'পরিবেশ', 'environment', false], ['Media', 'গণমাধ্যম', 'media', false],
        ];
        $watch = [
            ['Live TV', 'সরাসরি টিভি', 'live-tv'], ['Drama', 'নাটক', 'drama'],
            ['Documentary', 'প্রামাণ্যচিত্র', 'documentary'], ['Culture', 'সংস্কৃতি ও সংগীত', 'culture'],
            ['Kids', 'শিশু', 'kids'],
        ];

        NewsCategory::query()->update(['show_in_header' => false]);
        foreach ($news as $position => [$name, $nameBn, $slug, $showInHeader]) {
            NewsCategory::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'name_bn' => $nameBn, 'position' => $position, 'is_active' => true,
                'show_in_header' => $showInHeader,
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
