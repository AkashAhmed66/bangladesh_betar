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
            $table->boolean('show_in_header')->default(false)->after('is_active')->index();
        });

        Schema::table('watch_categories', function (Blueprint $table): void {
            $table->boolean('show_in_header')->default(false)->after('is_active')->index();
        });

        DB::table('news_categories')
            ->whereIn('slug', ['bangladesh', 'economy', 'climate'])
            ->update(['show_in_header' => true]);

        DB::table('watch_categories')
            ->whereIn('slug', ['live-tv', 'drama', 'documentary'])
            ->update(['show_in_header' => true]);
    }

    public function down(): void
    {
        Schema::table('watch_categories', function (Blueprint $table): void {
            $table->dropColumn('show_in_header');
        });

        Schema::table('news_categories', function (Blueprint $table): void {
            $table->dropColumn('show_in_header');
        });
    }
};
