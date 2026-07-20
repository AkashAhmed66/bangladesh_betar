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

        // M26 — reported comments/content feeding moderation queue (FR-ENG-04)
        Schema::create('content_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('reportable'); // Comment | AudioAsset | ...
            $table->string('reason', 50); // abuse | spam | copyright | inappropriate | other
            $table->text('details')->nullable();
            $table->string('status', 15)->default('pending')->index(); // pending | reviewed | actioned | dismissed
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });

        // M26 — "report an issue" on playable items (FR-ENG-07)
        Schema::create('issue_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audio_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('issue_type', 30); // broken_audio | wrong_metadata | inappropriate | other
            $table->text('description')->nullable();
            $table->string('status', 15)->default('open')->index(); // open | in_progress | resolved | dismissed
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });

        // M26 — rights-holder complaints & takedown workflow (FR-ENG-08)
        Schema::create('takedown_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('complainant_name');
            $table->string('complainant_email')->nullable();
            $table->string('organization')->nullable();
            $table->foreignId('audio_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason');
            $table->string('status', 20)->default('received')->index();
            // received | investigating | upheld | rejected | resolved
            $table->boolean('content_unpublished')->default(false);
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('takedown_requests');
        Schema::dropIfExists('issue_reports');
        Schema::dropIfExists('content_reports');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('comments');
    }
};
