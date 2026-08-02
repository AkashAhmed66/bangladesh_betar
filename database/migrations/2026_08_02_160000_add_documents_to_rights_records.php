<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-CPR-02 — the submitter now provides the copyright documents when they
 * "Submit for Rights" after the approval workflow completes. Stores an array
 * of {path, name} entries on the private disk (rights-docs/{asset}).
 *
 * Additive and idempotent — the deploy entrypoint runs `migrate` on every boot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rights_records', 'documents')) {
            Schema::table('rights_records', function (Blueprint $table): void {
                $table->json('documents')->nullable()->after('contract_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rights_records', 'documents')) {
            Schema::table('rights_records', function (Blueprint $table): void {
                $table->dropColumn('documents');
            });
        }
    }
};
