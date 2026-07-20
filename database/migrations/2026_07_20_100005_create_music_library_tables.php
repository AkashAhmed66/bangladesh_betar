<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M05/M08 — albums are first-class browsable entities (FR-SNG-09)
        Schema::create('albums', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->string('album_type', 20)->default('album'); // album | film | compilation | single
            $table->string('artwork_path')->nullable();
            $table->unsignedInteger('year')->nullable();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // M08 — song records on top of audio assets
        Schema::create('songs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('album_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('track_number')->nullable();
            $table->foreignId('genre_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mood_id')->nullable()->constrained()->nullOnDelete();
            $table->string('version_type', 20)->default('original')->index(); // original | live | remastered | instrumental | cover
            $table->foreignId('master_song_id')->nullable()->constrained('songs')->nullOnDelete(); // version family (FR-SNG-03)
            $table->unsignedInteger('release_year')->nullable();
            $table->longText('lyrics')->nullable();
            $table->longText('lyrics_bn')->nullable();
            $table->boolean('mood_genre_verified')->default(false); // AI classification verified (FR-SNG-10)
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('artist_song', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('singer'); // singer | composer | lyricist
            $table->unique(['song_id', 'artist_id', 'role']);
        });

        Schema::create('album_artist', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->unique(['album_id', 'artist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_artist');
        Schema::dropIfExists('artist_song');
        Schema::dropIfExists('songs');
        Schema::dropIfExists('albums');
    }
};
