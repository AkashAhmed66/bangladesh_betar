<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<int, string> */
    private const FIXED_HEADER_SLUGS = [
        'bangladesh',
        'politics',
        'world',
        'business',
        'sports',
        'entertainment',
        'jobs',
        'lifestyle',
        'video',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('news_categories')->update(['show_in_header' => false]);

            foreach (self::FIXED_HEADER_SLUGS as $position => $slug) {
                DB::table('news_categories')->where('slug', $slug)->update([
                    'position' => $position,
                    'is_active' => true,
                    'show_in_header' => true,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('news_categories')
            ->whereIn('slug', self::FIXED_HEADER_SLUGS)
            ->update(['show_in_header' => false, 'updated_at' => now()]);
    }
};
