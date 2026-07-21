<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M12 — non-destructive edit sessions (FR-EDT-05)
        Schema::create('edit_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_version_id')->nullable()->constrained('audio_versions')->nullOnDelete();
            $table->foreignId('output_version_id')->nullable()->constrained('audio_versions')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->json('edl')->nullable(); // edit decision list: [{op, start, end, params}]
            $table->string('status', 20)->default('in_progress')->index();
            // in_progress | submitted | approved | rejected | restored
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edit_sessions');
    }
};
