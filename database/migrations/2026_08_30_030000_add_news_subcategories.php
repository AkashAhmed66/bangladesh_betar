<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_categories', function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('news_categories')->nullOnDelete();
        });

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

        $now = now();
        foreach ($subcategories as $parentSlug => $children) {
            $parentId = DB::table('news_categories')->where('slug', $parentSlug)->value('id');
            if (! $parentId) {
                continue;
            }

            foreach ($children as $position => [$name, $nameBn, $slug]) {
                DB::table('news_categories')->updateOrInsert(['slug' => $slug], [
                    'parent_id' => $parentId,
                    'name' => $name,
                    'name_bn' => $nameBn,
                    'description' => $name.' reporting and updates.',
                    'description_bn' => $nameBn.' বিষয়ক সংবাদ ও হালনাগাদ।',
                    'position' => $position,
                    'is_active' => true,
                    'show_in_header' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $children = DB::table('news_categories')->whereNotNull('parent_id')->get(['id', 'parent_id']);
        foreach ($children as $child) {
            $parentName = DB::table('news_categories')->where('id', $child->parent_id)->value('name');
            DB::table('news_articles')->where('news_category_id', $child->id)->update([
                'news_category_id' => $child->parent_id,
                'category' => $parentName,
            ]);
        }
        DB::table('news_categories')->whereNotNull('parent_id')->delete();

        Schema::table('news_categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
