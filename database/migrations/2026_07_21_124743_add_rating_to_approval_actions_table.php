<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A reviewer may optionally rate the content's quality (1-5) alongside
     * their approval comment, so the Approval History carries both a
     * judgement and a score for the record (FR-WRK-06).
     */
    public function up(): void
    {
        Schema::table('approval_actions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('rating')->nullable()->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('approval_actions', function (Blueprint $table): void {
            $table->dropColumn('rating');
        });
    }
};
