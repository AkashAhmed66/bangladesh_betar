<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_recordings', function (Blueprint $table): void {
            $table->boolean('is_published')->default(true)->after('status');
            $table->timestamp('published_at')->nullable()->after('is_published');
            $table->index(
                ['is_published', 'status', 'published_at'],
                'broadcast_recordings_public_index',
            );
        });

        DB::table('broadcast_recordings')
            ->where('status', 'complete')
            ->update([
                'is_published' => true,
                'published_at' => DB::raw('COALESCE(ended_at, updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('broadcast_recordings', function (Blueprint $table): void {
            $table->dropIndex('broadcast_recordings_public_index');
            $table->dropColumn(['is_published', 'published_at']);
        });
    }
};
