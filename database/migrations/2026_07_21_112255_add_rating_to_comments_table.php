<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A comment may optionally carry the star rating (1-5) the listener gave
     * at the time they posted it, so the public "Comments and Ratings"
     * section can show both together. The `ratings` table remains the
     * source of truth for the asset's aggregate avg_rating/rating_count;
     * this column is a display-time snapshot only.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropColumn('rating');
        });
    }
};
