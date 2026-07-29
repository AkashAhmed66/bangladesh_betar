<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The engagement-tables migration (2026_07_20_100013) was edited in place to
     * add the `community_submissions` and `feedback` tables after it had already
     * been run in production. Laravel records that migration as "ran" and will
     * never re-apply it, so those two tables were never created on the drifted
     * database — the Community Inbox then fails with:
     *   Base table or view not found: 1146 Table '…community_submissions' doesn't exist
     *
     * This forward-only, idempotent migration creates whichever of the two tables
     * is missing. It is a no-op on databases (e.g. local) that already have them.
     * The table definitions are copied verbatim from the original migration.
     */
    public function up(): void
    {
        if (! Schema::hasTable('community_submissions')) {
            // M26 — unified "Community Inbox": abuse reports, content issues and
            // general feedback share one submission + status workflow
            // (FR-ENG-04/07/09).
            Schema::create('community_submissions', function (Blueprint $table): void {
                $table->id();
                $table->string('type', 20)->index();            // content_report | issue_report | feedback
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // reporter / submitter
                $table->nullableMorphs('subject');              // Comment | AudioAsset (null for feedback)
                $table->string('category', 50)->nullable();     // reason / issue_type / feedback category
                $table->string('subject_line')->nullable();     // optional title (feedback subject)
                $table->text('message')->nullable();            // details / description / message
                $table->string('status', 20)->default('new')->index(); // new | in_progress | resolved | dismissed
                $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('handled_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('feedback')) {
            // M26 — general feedback channel (FR-ENG-09)
            Schema::create('feedback', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('category', 30)->default('general'); // general | suggestion | complaint | technical
                $table->string('subject')->nullable();
                $table->text('message');
                $table->string('status', 15)->default('new')->index(); // new | read | responded | closed
                $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally does not drop the tables: they may have been created by
        // the original 2026_07_20_100013 migration on non-drifted databases, so
        // dropping here would remove data this migration did not create.
    }
};
