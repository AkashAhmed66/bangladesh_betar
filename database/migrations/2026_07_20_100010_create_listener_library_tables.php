<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M08/M17/M24 — playlists (listener-created and editorial/curated)
        Schema::create('playlists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // null => editorial
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('artwork_path')->nullable();
            $table->boolean('is_editorial')->default(false)->index(); // curated collection (FR-CUR-02)
            $table->boolean('is_public')->default(false);
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('playlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->morphs('playable'); // Song | PodcastEpisode | Episode | Story | AudioAsset
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // M17 — favourites / likes
        Schema::create('favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->timestamps();
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'favorites_unique');
        });

        // M17 — follow artists / programmes / podcast channels / playlists
        Schema::create('follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('followable');
            $table->timestamps();
            $table->unique(['user_id', 'followable_type', 'followable_id'], 'follows_unique');
        });

        // M17 — listening history + continue listening (FR-PLY-09, FR-PUB-14)
        Schema::create('play_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('playable');
            $table->foreignId('audio_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('progress_seconds')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('last_played_at')->index();
            $table->timestamps();
            $table->unique(['user_id', 'playable_type', 'playable_id'], 'play_histories_unique');
        });

        // M07 — persistent playback queue per signed-in user (FR-PLY-11)
        Schema::create('user_queues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->json('items'); // ordered [{type, id}]
            $table->string('repeat_mode', 10)->default('off'); // off | all | one
            $table->boolean('shuffle')->default(false);
            $table->timestamps();
        });

        // M06 — saved searches (FR-SRC-06)
        Schema::create('saved_searches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('query');
            $table->boolean('alert_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('user_queues');
        Schema::dropIfExists('play_histories');
        Schema::dropIfExists('follows');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('playlist_items');
        Schema::dropIfExists('playlists');
    }
};
