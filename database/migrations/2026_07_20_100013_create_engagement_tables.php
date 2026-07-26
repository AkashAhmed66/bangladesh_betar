<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M26 — comments with moderation states (FR-ENG-01/03/05)
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable');
            $table->text('body');
            $table->string('status', 15)->default('pending')->index(); // pending | approved | hidden | removed
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // M26 — one rating per user per item (FR-ENG-02)
        Schema::create('ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('ratable');
            $table->unsignedTinyInteger('rating'); // 1..5
            $table->timestamps();
            $table->unique(['user_id', 'ratable_type', 'ratable_id'], 'ratings_unique');
        });

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

    public function down(): void
    {
        Schema::dropIfExists('community_submissions');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('comments');
    }
};
