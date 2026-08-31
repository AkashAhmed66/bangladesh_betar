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

        $subcategories = [
            'bangladesh' => [
                ['National', 'জাতীয়', 'bangladesh-national'], ['Dhaka', 'ঢাকা', 'bangladesh-dhaka'],
                ['Chattogram', 'চট্টগ্রাম', 'bangladesh-chattogram'], ['Sylhet', 'সিলেট', 'bangladesh-sylhet'],
            ],
            'politics' => [
                ['Government', 'সরকার', 'politics-government'], ['Parliament', 'সংসদ', 'politics-parliament'],
                ['Elections', 'নির্বাচন', 'politics-elections'], ['Political Parties', 'রাজনৈতিক দল', 'politics-parties'],
            ],
            'world' => [
                ['Asia', 'এশিয়া', 'world-asia'], ['Europe', 'ইউরোপ', 'world-europe'],
                ['Americas', 'আমেরিকা', 'world-americas'], ['Middle East', 'মধ্যপ্রাচ্য', 'world-middle-east'],
            ],
            'business' => [
                ['Economy', 'অর্থনীতি', 'business-economy'], ['Banking', 'ব্যাংকিং', 'business-banking'],
                ['Markets', 'বাজার', 'business-markets'], ['Industry', 'শিল্প', 'business-industry'],
            ],
            'sports' => [
                ['Cricket', 'ক্রিকেট', 'sports-cricket'], ['Football', 'ফুটবল', 'sports-football'],
                ['Local Sports', 'দেশের খেলা', 'sports-local'], ['International Sports', 'আন্তর্জাতিক খেলা', 'sports-international'],
            ],
            'entertainment' => [
                ['Television', 'টেলিভিশন', 'entertainment-television'], ['OTT', 'ওটিটি', 'entertainment-ott'],
                ['Films', 'চলচ্চিত্র', 'entertainment-films'], ['Music', 'গান', 'entertainment-music'],
                ['Drama', 'নাটক', 'entertainment-drama'], ['Interviews', 'আলাপন', 'entertainment-interviews'],
            ],
            'jobs' => [
                ['Government Jobs', 'সরকারি চাকরি', 'jobs-government'], ['Private Jobs', 'বেসরকারি চাকরি', 'jobs-private'],
                ['Career Advice', 'ক্যারিয়ার পরামর্শ', 'jobs-career'], ['Results', 'ফলাফল', 'jobs-results'],
            ],
            'lifestyle' => [
                ['Health', 'স্বাস্থ্য', 'lifestyle-health'], ['Education', 'শিক্ষা', 'lifestyle-education'],
                ['Travel', 'ভ্রমণ', 'lifestyle-travel'], ['Food', 'খাবার', 'lifestyle-food'],
                ['Fashion', 'ফ্যাশন', 'lifestyle-fashion'],
            ],
            'video' => [
                ['News Video', 'সংবাদ ভিডিও', 'video-news'], ['Interviews', 'সাক্ষাৎকার', 'video-interviews'],
                ['Explainers', 'ব্যাখ্যা', 'video-explainers'], ['Documentaries', 'প্রামাণ্যচিত্র', 'video-documentaries'],
            ],
        ];

        foreach ($subcategories as $parentSlug => $children) {
            $parent = NewsCategory::query()->where('slug', $parentSlug)->first();
            if (! $parent) {
                continue;
            }

            foreach ($children as $position => [$name, $nameBn, $slug]) {
                NewsCategory::query()->updateOrCreate(['slug' => $slug], [
                    'parent_id' => $parent->id,
                    'name' => $name,
                    'name_bn' => $nameBn,
                    'description' => $name.' reporting and updates.',
                    'description_bn' => $nameBn.' বিষয়ক সংবাদ ও হালনাগাদ।',
                    'position' => $position,
                    'is_active' => true,
                    'show_in_header' => false,
                ]);
            }
        }

        $watch = [
            ['Live TV', 'সরাসরি টিভি', 'live-tv'],
            ['Movies', 'চলচ্চিত্র', 'movies'],
            ['Series', 'ধারাবাহিক', 'series'],
            ['Short Films', 'স্বল্পদৈর্ঘ্য চলচ্চিত্র', 'short-films'],
            ['Songs', 'গান ও সংগীত', 'songs'],
            ['Drama', 'নাটক', 'drama'],
            ['Documentary', 'প্রামাণ্যচিত্র', 'documentary'],
            ['Culture', 'সংস্কৃতি ও ঐতিহ্য', 'culture'],
            ['Kids', 'শিশু', 'kids'],
            ['Comedy', 'কৌতুক ও রম্য', 'comedy'],
            ['Living and Culture', 'জীবনধারা ও সংস্কৃতি', 'living-and-culture'],
            ['Horror', 'ভৌতিক ও রহস্য', 'horror'],
            ['News and Current Affairs', 'সংবাদ ও সমসাময়িক', 'news-and-current-affairs'],
            ['Popular Programmes', 'জনপ্রিয় অনুষ্ঠান', 'popular-programmes'],
            ['Crime Drama', 'ক্রাইম ড্রামা', 'crime-drama'],
            ['Trending', 'ট্রেন্ডিং', 'trending'],
            ['New Release', 'নতুন মুক্তি', 'new-release'],
        ];

        foreach ($watch as $position => [$name, $nameBn, $slug]) {
            WatchCategory::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name, 'name_bn' => $nameBn, 'position' => $position, 'is_active' => true,
                'show_in_header' => in_array($slug, ['live-tv', 'movies', 'series', 'short-films', 'songs', 'drama', 'documentary'], true),
            ]);
        }

        $this->command?->info('News and Watch portal categories seeded.');
    }
}
