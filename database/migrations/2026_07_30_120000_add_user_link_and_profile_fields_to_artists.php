<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M01/M08 — link an Artist to a user account and give artists a richer public
 * profile (verified badge, cover banner, social links).
 *
 * Additive and idempotent (guarded with hasColumn) so it applies cleanly on
 * databases where earlier migrations already ran — the deploy entrypoint runs
 * `migrate` on every boot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('artists', function (Blueprint $table): void {
            if (! Schema::hasColumn('artists', 'user_id')) {
                // Nullable + unique: catalogue artists may have no account, and an
                // account maps to at most one artist profile.
                $table->foreignId('user_id')->nullable()->after('id')
                    ->unique()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('artists', 'is_verified')) {
                // Admin-controlled verified badge.
                $table->boolean('is_verified')->default(false)->after('is_featured')->index();
            }
            if (! Schema::hasColumn('artists', 'cover_path')) {
                $table->string('cover_path')->nullable()->after('photo_path');
            }
            if (! Schema::hasColumn('artists', 'social_links')) {
                // { website, facebook, instagram, youtube, twitter, spotify }
                $table->json('social_links')->nullable()->after('cover_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table): void {
            if (Schema::hasColumn('artists', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
            foreach (['is_verified', 'cover_path', 'social_links'] as $column) {
                if (Schema::hasColumn('artists', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
