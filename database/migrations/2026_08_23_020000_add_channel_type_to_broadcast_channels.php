<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_channels', function (Blueprint $table): void {
            $table->string('channel_type', 10)->default('audio')->after('room_name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_channels', function (Blueprint $table): void {
            $table->dropIndex(['channel_type']);
            $table->dropColumn('channel_type');
        });
    }
};
