<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M27 — Live audio broadcasting.
 *
 * broadcast_channels : a named live channel a broadcaster can go on-air with.
 * broadcast_sessions : one on-air session (a channel can have history; at most
 *                      one 'live' session at a time). "Which channel is live"
 *                      is derived from a live session, kept fresh by LiveKit
 *                      webhooks (room_finished ends it, participant_* counts).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_channels', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('station_id')->nullable()->constrained()->nullOnDelete();
            $table->string('room_name')->unique();          // LiveKit room, stable per channel
            $table->string('artwork_path')->nullable();
            $table->boolean('is_active')->default(true)->index(); // channel enabled for broadcasting
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('broadcast_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broadcast_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('broadcaster_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('room_name');
            $table->string('title')->nullable();            // optional topic for this session
            $table->string('status', 10)->default('live')->index(); // live | ended
            $table->unsignedInteger('current_listeners')->default(0);
            $table->unsignedInteger('peak_listeners')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['broadcast_channel_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_sessions');
        Schema::dropIfExists('broadcast_channels');
    }
};
