<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_clips', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->string('slug')->unique();
            // Creator / channel
            $table->string('creator_name')->nullable();
            $table->string('creator_handle')->nullable();
            $table->string('creator_avatar_path')->nullable();
            // Vertical video (9:16 aspect ratio)
            $table->string('video_path')->nullable();
            // Thumbnail / poster
            $table->string('thumbnail_path')->nullable();
            // Audio track info (displayed as spinning disc)
            $table->string('audio_track')->nullable();
            // Comma-separated hashtag strings, e.g. "#betar,#culture"
            $table->string('hashtags')->nullable();
            // Engagement counters (managed server-side for display)
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('dislikes_count')->default(0);
            // Publishing
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_clips');
    }
};
