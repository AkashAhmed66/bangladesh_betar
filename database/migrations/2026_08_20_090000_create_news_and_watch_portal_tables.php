<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('summary');
            $table->string('category', 80)->index();
            $table->json('body');
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('read_time_minutes')->default(3);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('watch_shows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('eyebrow', 120)->nullable();
            $table->text('description');
            $table->string('category', 60)->index();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('rating', 20)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('watch_episodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('watch_show_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(1);
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('video_path')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();

            $table->index(['watch_show_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_episodes');
        Schema::dropIfExists('watch_shows');
        Schema::dropIfExists('news_articles');
    }
};
