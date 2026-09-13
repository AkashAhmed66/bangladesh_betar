<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_books', function (Blueprint $table): void {
            $table->string('artwork_path')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('audio_books', function (Blueprint $table): void {
            $table->dropColumn('artwork_path');
        });
    }
};
