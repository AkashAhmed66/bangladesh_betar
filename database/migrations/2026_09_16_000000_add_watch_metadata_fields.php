<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_shows', function (Blueprint $table): void {
            $table->string('trailer_path')->nullable()->after('image_path');
            $table->string('age_restriction', 20)->nullable()->after('rating');
            $table->json('genres')->nullable()->after('category');
            $table->json('creators')->nullable()->after('genres');
            $table->json('cast')->nullable()->after('creators');
            $table->json('audio_languages')->nullable()->after('cast');
            $table->json('subtitle_languages')->nullable()->after('audio_languages');
        });

        Schema::table('watch_episodes', function (Blueprint $table): void {
            $table->text('summary')->nullable()->after('description');
            $table->text('summary_bn')->nullable()->after('summary');
            $table->json('audio_languages')->nullable()->after('summary_bn');
            $table->json('subtitle_languages')->nullable()->after('audio_languages');
        });
    }

    public function down(): void
    {
        Schema::table('watch_episodes', function (Blueprint $table): void {
            $table->dropColumn(['summary', 'summary_bn', 'audio_languages', 'subtitle_languages']);
        });

        Schema::table('watch_shows', function (Blueprint $table): void {
            $table->dropColumn([
                'trailer_path',
                'age_restriction',
                'genres',
                'creators',
                'cast',
                'audio_languages',
                'subtitle_languages',
            ]);
        });
    }
};
