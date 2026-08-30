<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<int, array{name:string, name_bn:string, slug:string, description:string, description_bn:string}> */
    private const CATEGORIES = [
        ['name' => 'Bangladesh', 'name_bn' => 'বাংলাদেশ', 'slug' => 'bangladesh', 'description' => 'News and public-interest reporting from across Bangladesh.', 'description_bn' => 'বাংলাদেশের সংবাদ ও জনস্বার্থমূলক প্রতিবেদন।'],
        ['name' => 'Politics', 'name_bn' => 'রাজনীতি', 'slug' => 'politics', 'description' => 'Government, parliament, elections and public policy.', 'description_bn' => 'সরকার, সংসদ, নির্বাচন ও জননীতি বিষয়ক সংবাদ।'],
        ['name' => 'World', 'name_bn' => 'বিশ্ব', 'slug' => 'world', 'description' => 'International news and global affairs.', 'description_bn' => 'আন্তর্জাতিক সংবাদ ও বিশ্ব পরিস্থিতি।'],
        ['name' => 'Business', 'name_bn' => 'বাণিজ্য', 'slug' => 'business', 'description' => 'Business, markets, economy and public finance.', 'description_bn' => 'ব্যবসা, বাজার, অর্থনীতি ও সরকারি অর্থব্যবস্থা।'],
        ['name' => 'Sports', 'name_bn' => 'খেলা', 'slug' => 'sports', 'description' => 'Local and international sports reporting.', 'description_bn' => 'দেশি ও আন্তর্জাতিক খেলার সংবাদ।'],
        ['name' => 'Entertainment', 'name_bn' => 'বিনোদন', 'slug' => 'entertainment', 'description' => 'Film, television, music and cultural entertainment.', 'description_bn' => 'চলচ্চিত্র, টেলিভিশন, সংগীত ও সাংস্কৃতিক বিনোদন।'],
        ['name' => 'Jobs', 'name_bn' => 'চাকরি', 'slug' => 'jobs', 'description' => 'Employment news, careers and opportunities.', 'description_bn' => 'চাকরির খবর, পেশা ও কর্মসংস্থানের সুযোগ।'],
        ['name' => 'Lifestyle', 'name_bn' => 'জীবনযাপন', 'slug' => 'lifestyle', 'description' => 'Health, education, travel and everyday life.', 'description_bn' => 'স্বাস্থ্য, শিক্ষা, ভ্রমণ ও দৈনন্দিন জীবনযাপন।'],
        ['name' => 'Video', 'name_bn' => 'ভিডিও', 'slug' => 'video', 'description' => 'Video reports, interviews and explainers.', 'description_bn' => 'ভিডিও প্রতিবেদন, সাক্ষাৎকার ও ব্যাখ্যামূলক আয়োজন।'],
    ];

    /** @var array<string, string> */
    private const LEGACY_MAPPINGS = [
        'economy' => 'business',
        'climate' => 'bangladesh',
        'culture' => 'entertainment',
        'science' => 'lifestyle',
        'environment' => 'bangladesh',
        'media' => 'video',
        'motamot' => 'politics',
        'opinion' => 'politics',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('news_categories')->update(['show_in_header' => false]);

            $now = now();
            foreach (self::CATEGORIES as $position => $category) {
                DB::table('news_categories')->upsert(
                    [[
                        ...$category,
                        'position' => $position,
                        'is_active' => true,
                        'show_in_header' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]],
                    ['slug'],
                    ['name', 'name_bn', 'description', 'description_bn', 'position', 'is_active', 'show_in_header', 'updated_at'],
                );
            }

            foreach (self::LEGACY_MAPPINGS as $legacySlug => $destinationSlug) {
                $legacy = DB::table('news_categories')->where('slug', $legacySlug)->first();
                $destination = DB::table('news_categories')->where('slug', $destinationSlug)->first();
                if ($legacy === null || $destination === null || $legacy->id === $destination->id) {
                    continue;
                }

                DB::table('news_articles')
                    ->where('news_category_id', $legacy->id)
                    ->orWhere('category', $legacy->name)
                    ->update([
                        'news_category_id' => $destination->id,
                        'category' => $destination->name,
                        'updated_at' => $now,
                    ]);

                DB::table('news_categories')->where('id', $legacy->id)->delete();
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('news_categories')->update(['show_in_header' => false]);
            $now = now();
            $legacyHeadings = [
                ['name' => 'Bangladesh', 'name_bn' => 'বাংলাদেশ', 'slug' => 'bangladesh', 'position' => 0],
                ['name' => 'Economy', 'name_bn' => 'অর্থনীতি', 'slug' => 'economy', 'position' => 1],
                ['name' => 'Climate', 'name_bn' => 'জলবায়ু', 'slug' => 'climate', 'position' => 2],
            ];

            foreach ($legacyHeadings as $category) {
                DB::table('news_categories')->upsert(
                    [[
                        ...$category,
                        'description' => null,
                        'description_bn' => null,
                        'is_active' => true,
                        'show_in_header' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]],
                    ['slug'],
                    ['name', 'name_bn', 'position', 'is_active', 'show_in_header', 'updated_at'],
                );
            }
        });
    }
};
