<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_recordings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broadcast_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('egress_id')->nullable()->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->string('disk', 50)->default('broadcast_recordings');
            $table->string('file_path')->nullable();
            $table->string('format', 10)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_recordings');
    }
};
