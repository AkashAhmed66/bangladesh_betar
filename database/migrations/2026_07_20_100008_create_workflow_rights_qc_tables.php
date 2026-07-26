<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M13 — configurable multi-stage workflows per content type (FR-WRK-01)
        Schema::create('workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('content_type', 30)->index(); // song | podcast | advert | programme | historical | story | default
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('escalation_hours')->default(72); // FR-WRK-08
            $table->timestamps();
        });

        Schema::create('workflow_stages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. Technical QC, Editorial Review, Copyright Review, Management Approval
            $table->unsignedInteger('sequence');
            $table->string('approver_role', 60); // spatie role name whose members can act
            $table->timestamps();
            $table->unique(['workflow_id', 'sequence']);
        });

        // workflow instances attached to any approvable item
        Schema::create('approvals', function (Blueprint $table): void {
            $table->id();
            $table->morphs('approvable'); // audio_assets, podcast_episodes, edit_sessions ...
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_stage_id')->nullable()->constrained('workflow_stages')->nullOnDelete();
            $table->string('status', 25)->default('pending')->index();
            // pending | changes_requested | approved | rejected | cancelled
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('approval_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('approval_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 25); // submitted | approved | rejected | correction_requested | resubmitted | escalated
            $table->text('comments')->nullable(); // mandatory on rejection (FR-WRK-03)
            $table->timestamps();
        });

        // M14 — rights holders & rights records
        Schema::create('rights_holders', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('holder_type', 20)->default('person'); // person | organization
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('rights_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rights_holder_id')->nullable()->constrained()->nullOnDelete();
            $table->json('rights_types'); // ["broadcast","streaming","download","commercial"]
            $table->string('territory')->default('Bangladesh');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable()->index(); // expiry notifications (FR-CPR-06)
            $table->boolean('royalty_required')->default(false);
            $table->text('royalty_notes')->nullable();
            $table->string('status', 20)->default('pending')->index();
            // cleared | restricted | pending | disputed | expired
            $table->string('contract_path')->nullable(); // FR-CPR-02
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rights_records');
        Schema::dropIfExists('rights_holders');
        Schema::dropIfExists('approval_actions');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('workflow_stages');
        Schema::dropIfExists('workflows');
    }
};
