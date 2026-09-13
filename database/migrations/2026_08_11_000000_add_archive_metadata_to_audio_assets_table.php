<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_assets', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->index()->after('published_at');
            $table->foreignId('archived_by')->nullable()->after('archived_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audio_assets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn('archived_at');
        });
    }
};
