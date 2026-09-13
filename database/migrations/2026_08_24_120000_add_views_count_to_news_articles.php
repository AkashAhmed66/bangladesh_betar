<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->unsignedBigInteger('views_count')->default(0)->index()->after('read_time_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->dropColumn('views_count');
        });
    }
};
