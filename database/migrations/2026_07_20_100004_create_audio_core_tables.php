<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M02/M04 — the central asset record
        Schema::create('audio_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('archive_no', 40)->unique(); // permanent archive ID, e.g. BB-2026-000001
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->string('content_type', 30)->default('programme')->index();
            // song | programme | podcast | story | news | interview | drama | speech | jingle | psa | advert | voice_over | historical
            $table->foreignId('station_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 30)->default('upload'); // upload | bulk | ftp | studio | live_recording | digitization | migration
            $table->string('original_filename')->nullable();
            $table->string('artwork_path')->nullable();

            // technical metadata (auto-extracted at ingestion — FR-ING-09)
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('format', 10)->nullable();       // wav | bwf | flac | mp3 | aac | m4a | ogg | aiff
            $table->unsignedInteger('sample_rate')->nullable();
            $table->unsignedTinyInteger('bit_depth')->nullable();
            $table->unsignedTinyInteger('channels')->nullable();
            $table->unsignedInteger('bitrate_kbps')->nullable();
            $table->decimal('loudness_lufs', 6, 2)->nullable();
            $table->decimal('peak_db', 6, 2)->nullable();
            $table->decimal('silence_percent', 5, 2)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable()->index();
            $table->json('waveform_peaks')->nullable(); // pre-computed waveform data (FR-PLY-16)

            // lifecycle & protection
            $table->string('status', 30)->default('draft')->index();
            // draft | pending_qc | qc_failed | in_review | pending_approval | approved | published | rejected | unpublished | archived
            $table->string('access_level', 20)->default('internal')->index(); // public | internal | restricted
            $table->string('rights_status', 20)->default('pending')->index(); // cleared | restricted | pending | disputed | expired
            $table->boolean('is_premium')->default(false)->index();     // premium-flagged content (M18)
            $table->boolean('is_public_service')->default(false);       // always free, never ads (FR-SUB-11)
            $table->boolean('allow_comments')->default(true);
            $table->string('content_warning')->nullable();
            $table->date('recorded_on')->nullable();
            $table->date('first_broadcast_on')->nullable()->index();    // powers "On This Day" (FR-CUR-04)
            $table->timestamp('published_at')->nullable()->index();
            $table->json('custom_fields')->nullable(); // administrator-defined metadata (FR-MET-03)

            // denormalized engagement counters
            $table->unsignedBigInteger('play_count')->default(0);
            $table->unsignedBigInteger('favorite_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // M04 — derived version families (master is immutable — FR-REP-03)
        Schema::create('audio_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->string('version_type', 30)->index();
            // preservation_master | production_master | broadcast | online | preview | social | clip
            $table->string('label')->nullable();
            $table->string('file_path');
            $table->string('format', 10)->nullable();
            $table->unsignedInteger('bitrate_kbps')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignId('derived_from_id')->nullable()->constrained('audio_versions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('audio_asset_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unique(['audio_asset_id', 'tag_id']);
        });

        Schema::create('audio_asset_artist', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('performer'); // presenter | producer | narrator | speaker | performer
            $table->unique(['audio_asset_id', 'artist_id', 'role']);
        });

        // M03 — digitization register (physical media)
        Schema::create('media_items', function (Blueprint $table): void {
            $table->id();
            $table->string('item_code', 40)->unique();
            $table->string('title');
            $table->string('media_type', 20)->index(); // reel | cassette | dat | vinyl | cd | minidisc
            $table->string('condition', 20)->default('good'); // good | fair | poor | critical
            $table->string('location')->nullable();
            $table->string('priority', 10)->default('medium'); // high | medium | low
            $table->string('status', 20)->default('registered')->index();
            // registered | in_progress | captured | restored | qc_pending | qc_passed | archived | failed
            $table->foreignId('station_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audio_asset_id')->nullable()->constrained()->nullOnDelete(); // link to digitized asset (FR-DIG-07)
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('digitized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('digitized_at')->nullable();
            $table->text('restoration_notes')->nullable(); // FR-DIG-04
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_items');
        Schema::dropIfExists('audio_asset_artist');
        Schema::dropIfExists('audio_asset_tag');
        Schema::dropIfExists('audio_versions');
        Schema::dropIfExists('audio_assets');
    }
};
