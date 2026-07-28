<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen audit_logs.description from VARCHAR(255) to TEXT so entries that
     * embed an external error message (e.g. a cURL/analysis-service failure)
     * are stored in full instead of overflowing the column. Additive and
     * idempotent — safe to run on an already-migrated production database.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('audit_logs', 'description')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('description')->nullable()->change();
        });
    }
};
