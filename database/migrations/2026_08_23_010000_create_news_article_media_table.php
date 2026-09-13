<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_article_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $table->string('media_type', 20)->index();
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['news_article_id', 'media_type', 'position'], 'news_media_article_type_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_media');
    }
};
