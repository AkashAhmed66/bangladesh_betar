<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Audio Books (M31): PDF/text converted to narrated audio in BOTH male and
 * female voices, reviewed via an approval step, then published to premium
 * listeners in the public app (read-along text + audio).
 *
 * Also creates the permissions: `audiobooks.use` (create/manage own books —
 * artists + content staff) and `audiobooks.approve` (approve/reject +
 * publish). Guarded — the entrypoint runs `migrate` on every boot.
 */
return new class extends Migration
{
    private const APPROVER_ROLES = ['Super Administrator', 'Archive Administrator', 'Approver'];

    public function up(): void
    {
        if (! Schema::hasTable('audio_books')) {
            Schema::create('audio_books', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->string('language', 5)->default('auto');   // en | bn | auto (resolved on generation)
                $table->string('source_type', 10);                // pdf | text
                $table->string('source_path')->nullable();        // uploaded PDF (private disk)
                $table->longText('text')->nullable();             // full text — powers public read-along
                $table->unsignedInteger('characters')->default(0);
                $table->boolean('used_ocr')->default(false);
                $table->string('engine', 10)->nullable();         // neural | espeak
                $table->string('status', 20)->default('generating')->index();
                // generating | failed | ready | pending_approval | published | rejected
                $table->text('error')->nullable();
                $table->string('audio_male_path')->nullable();
                $table->string('audio_female_path')->nullable();
                $table->unsignedInteger('duration_male')->default(0);
                $table->unsignedInteger('duration_female')->default(0);
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('review_comments')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
            });
        }

        $use = Permission::findOrCreate('audiobooks.use', 'web');
        $approve = Permission::findOrCreate('audiobooks.approve', 'web');

        Role::query()->where('guard_name', 'web')
            ->where('name', '!=', 'Listener')
            ->get()
            ->each(function (Role $role) use ($use, $approve): void {
                if (! $role->hasPermissionTo($use)) {
                    $role->givePermissionTo($use);
                }
                if (in_array($role->name, self::APPROVER_ROLES, true) && ! $role->hasPermissionTo($approve)) {
                    $role->givePermissionTo($approve);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_books');
        Permission::query()->whereIn('name', ['audiobooks.use', 'audiobooks.approve'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
