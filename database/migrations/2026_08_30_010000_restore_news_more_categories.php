<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<int, array{name:string, name_bn:string, slug:string, description:string, description_bn:string}> */
    private const CATEGORIES = [
        ['name' => 'Economy', 'name_bn' => 'অর্থনীতি', 'slug' => 'economy', 'description' => 'Economy, business and public finance.', 'description_bn' => 'অর্থনীতি, ব্যবসা ও সরকারি অর্থব্যবস্থা।'],
        ['name' => 'Climate', 'name_bn' => 'জলবায়ু', 'slug' => 'climate', 'description' => 'Climate change and resilience.', 'description_bn' => 'জলবায়ু পরিবর্তন ও সহনশীলতা।'],
        ['name' => 'Culture', 'name_bn' => 'সংস্কৃতি', 'slug' => 'culture', 'description' => 'Arts, heritage and cultural life.', 'description_bn' => 'শিল্প, ঐতিহ্য ও সাংস্কৃতিক জীবন।'],
        ['name' => 'Science', 'name_bn' => 'বিজ্ঞান', 'slug' => 'science', 'description' => 'Science, health and innovation.', 'description_bn' => 'বিজ্ঞান, স্বাস্থ্য ও উদ্ভাবন।'],
        ['name' => 'Environment', 'name_bn' => 'পরিবেশ', 'slug' => 'environment', 'description' => 'Environment and nature reporting.', 'description_bn' => 'পরিবেশ ও প্রকৃতি বিষয়ক প্রতিবেদন।'],
        ['name' => 'Media', 'name_bn' => 'গণমাধ্যম', 'slug' => 'media', 'description' => 'Media, broadcasting and communication.', 'description_bn' => 'গণমাধ্যম, সম্প্রচার ও যোগাযোগ।'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::CATEGORIES as $offset => $category) {
            DB::table('news_categories')->upsert(
                [[
                    ...$category,
                    'position' => 9 + $offset,
                    'is_active' => true,
                    'show_in_header' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['slug'],
                ['name', 'name_bn', 'description', 'description_bn', 'position', 'is_active', 'show_in_header', 'updated_at'],
            );
        }
    }

    public function down(): void
    {
        DB::table('news_categories')
            ->whereIn('slug', array_column(self::CATEGORIES, 'slug'))
            ->update(['is_active' => false, 'show_in_header' => false, 'updated_at' => now()]);
    }
};
