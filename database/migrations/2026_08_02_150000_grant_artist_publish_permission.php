<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Artists may publish/unpublish their OWN approved assets: the Artist role
 * gains `assets.publish`, while record visibility (no `records.view-all`)
 * keeps other users' assets out of reach and the publish gates (completed
 * approval workflow + cleared rights + online version) still apply.
 *
 * Data migration because seeders run only on first boot; idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = Role::findOrCreate('Artist', 'web');
        $permission = Permission::findOrCreate('assets.publish', 'web');

        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::query()->where(['name' => 'Artist', 'guard_name' => 'web'])
            ->first()?->revokePermissionTo('assets.publish');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
