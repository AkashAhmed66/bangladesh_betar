<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Timeline markers & segments for the Audio Studio visualization:
        // AI content markers, chapters, speaker/segment labels, and manual
        // annotations (FR-AIF / Audio Visualization spec).
        Schema::create('audio_markers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->string('marker_type', 20)->default('note')->index();
            // chapter | keyword | speaker | segment | applause | laughter | music
            // | speech | silence | ad | intro | outro | emotion | note
            $table->string('label');
            $table->decimal('start_seconds', 10, 3)->default(0);
            $table->decimal('end_seconds', 10, 3)->nullable(); // null = point marker
            $table->string('color', 20)->nullable();
            $table->boolean('is_ai')->default(false); // AI-generated draft vs human-set
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['audio_asset_id', 'start_seconds']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_markers');
    }
};
