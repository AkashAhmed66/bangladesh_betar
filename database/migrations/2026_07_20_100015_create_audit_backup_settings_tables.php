<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // M21 — append-only audit trail (FR-AUD-01/02)
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 60)->index(); // created | updated | deleted | login | logout | approved | published | refund_issued ...
            $table->nullableMorphs('auditable');
            $table->string('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->index();
        });

        // M22 — backup runs & integrity checks
        Schema::create('backup_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('backup_type', 15); // audio | database | metadata | full
            $table->string('target', 15)->default('local'); // local | offsite
            $table->string('status', 15)->default('running')->index(); // running | success | failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('integrity_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audio_asset_id')->constrained()->cascadeOnDelete();
            $table->string('result', 10)->default('ok'); // ok | corrupt
            $table->text('details')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        // Central key-value application settings (theme, streaming, moderation defaults ...)
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 40)->default('general')->index();
            $table->string('key', 80)->unique();
            $table->text('value')->nullable();
            $table->string('type', 15)->default('string'); // string | int | bool | json | color
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('integrity_checks');
        Schema::dropIfExists('backup_runs');
        Schema::dropIfExists('audit_logs');
    }
};
