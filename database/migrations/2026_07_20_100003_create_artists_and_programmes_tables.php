<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M05/M08 — person/entity records reusable across assets
        Schema::create('artists', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('slug')->unique();
            $table->string('artist_type', 30)->default('singer')->index();
            // singer | composer | lyricist | presenter | producer | voice_artist | narrator | speaker | band
            $table->text('bio')->nullable();
            $table->text('bio_bn')->nullable();
            $table->string('photo_path')->nullable();
            $table->date('born_on')->nullable();
            $table->date('died_on')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // M04 — Programme / Collection level of hierarchy
        Schema::create('programmes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('station_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('slug')->unique();
            $table->string('programme_type', 30)->default('programme')->index();
            // programme | drama | news | event | magazine | talk_show
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->string('artwork_path')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('artists');
    }
};
