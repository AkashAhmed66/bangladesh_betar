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
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audio_asset_id')->nullable()->constrained()->nullOnDelete();
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

        // M10 — individually addressable stories inside an episode (FR-EVT-02/03)
        Schema::create('stories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->string('storyteller_name')->nullable();
            $table->boolean('is_anonymous')->default(false); // FR-EVT-04
            $table->string('narrator')->nullable();
            $table->string('location')->nullable();
            $table->string('district', 60)->nullable()->index();
            $table->unsignedInteger('start_seconds')->default(0);
            $table->unsignedInteger('end_seconds')->default(0);
            $table->string('content_warning')->nullable(); // FR-EVT-07
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedBigInteger('play_count')->default(0);
            $table->timestamps();
        });

        // M10 — listener story submissions with consent (FR-EVT-05)
        Schema::create('story_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('submitter_name')->nullable();
            $table->string('contact')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('submission_type', 10)->default('text'); // text | audio
            $table->longText('content_text')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending | in_review | accepted | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('story_submissions');
        Schema::dropIfExists('stories');
        Schema::dropIfExists('episodes');
    }
};
