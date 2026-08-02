<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Record-level visibility: roles holding the new `records.view-all` permission
 * see every record in their permitted modules; other users see only records
 * they created. Adds the missing `created_by` columns and creates + grants the
 * permission to every existing staff role so nothing disappears on deploy —
 * restricting a role is then done by REVOKING the permission in the Roles UI.
 *
 * Additive and idempotent (hasColumn / findOrCreate guards) — the deploy
 * entrypoint runs `migrate` on every boot.
 */
return new class extends Migration
{
    /** Content tables that need a creator column for visibility scoping. */
    private const TABLES = [
        'songs', 'albums', 'artists', 'programmes', 'episodes',
        'podcast_channels', 'podcast_episodes', 'banners',
        'advertisers', 'ad_campaigns',
    ];

    /** Roles that never see the admin modules — no grant needed. */
    private const EXCLUDED_ROLES = ['Listener', 'Artist'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'created_by')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    // Nullable: legacy/seeded rows have no known creator and stay
                    // visible only to records.view-all holders.
                    $blueprint->foreignId('created_by')->nullable()
                        ->constrained('users')->nullOnDelete();
                });
            }
        }

        $permission = Permission::findOrCreate('records.view-all', 'web');

        Role::query()->where('guard_name', 'web')
            ->whereNotIn('name', self::EXCLUDED_ROLES)
            ->get()
            ->each(function (Role $role) use ($permission): void {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'created_by')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropConstrainedForeignId('created_by');
                });
            }
        }

        Permission::query()->where('name', 'records.view-all')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
