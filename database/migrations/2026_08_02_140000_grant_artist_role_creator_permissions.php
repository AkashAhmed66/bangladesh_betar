<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The Artist role becomes a creator role: upload/ingest recordings, manage
 * their own catalogue records (songs, albums, podcasts, programmes,
 * episodes), follow their approval status, watch comments on their
 * recordings, go on air, and see their own dashboard + analytics.
 *
 * Deliberately WITHOUT `records.view-all` (they see only records they
 * created) and without publish/approve/delete — staff review and publish.
 *
 * Data migration because seeders run only on first boot; idempotent
 * (grant-if-missing) so admin customisations of the role are preserved.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'dashboard.view',
        'assets.view', 'assets.upload', 'assets.edit',
        'editing.view', 'editing.use',
        'songs.view', 'songs.manage',
        'albums.view', 'albums.manage',
        'podcasts.view', 'podcasts.manage',
        'programmes.view', 'programmes.manage',
        'episodes.view', 'episodes.manage',
        'broadcasts.view', 'broadcasts.broadcast',
        'approvals.view',
        'moderation.view',
        'notifications.view',
    ];

    public function up(): void
    {
        $role = Role::findOrCreate('Artist', 'web');

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::findOrCreate($name, 'web');

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::query()->where(['name' => 'Artist', 'guard_name' => 'web'])->first();

        if ($role) {
            foreach (self::PERMISSIONS as $name) {
                $role->revokePermissionTo($name);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
