<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M04/M10 — programme episodes (radio programmes, event programmes like Bhoot FM)
        Schema::create('episodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audio_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('season_number')->default(1); // plain number, like podcast episodes
            $table->unsignedInteger('number')->nullable();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('artwork_path')->nullable();
            $table->date('broadcast_date')->nullable()->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('play_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // M09 — podcast channels & episodes
        Schema::create('podcast_channels', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('artwork_path')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('rss_enabled')->default(true);           // FR-POD-04
            $table->boolean('rss_include_premium')->default(false);  // external feeds: free episodes only
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('podcast_episodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('podcast_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audio_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('season_number')->default(1);
            $table->unsignedInteger('episode_number')->default(1);
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('artwork_path')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_premium')->default(false);
            $table->string('status', 20)->default('draft')->index(); // draft | scheduled | published | unpublished
            $table->timestamp('scheduled_at')->nullable();           // FR-POD-03
            $table->timestamp('published_at')->nullable();
            $table->json('chapters')->nullable();                    // [{title, start_seconds}]
            $table->unsignedBigInteger('play_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['podcast_channel_id', 'season_number', 'episode_number'], 'podcast_episode_unique');
        });

        Schema::create('podcast_episode_artist', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('podcast_episode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10)->default('host'); // host | guest
            $table->unique(['podcast_episode_id', 'artist_id', 'role'], 'podcast_episode_artist_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('podcast_episode_artist');
        Schema::dropIfExists('podcast_episodes');
        Schema::dropIfExists('podcast_channels');
        Schema::dropIfExists('episodes');
    }
};
