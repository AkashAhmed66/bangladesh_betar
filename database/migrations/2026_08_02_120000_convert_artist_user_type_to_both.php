<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Account types are now staff | listener | both. "Artist" is no longer a
 * top-level account type but a role held by a dual-app ("both") account, so
 * migrate any legacy user_type = 'artist' rows to 'both'. Their Artist role is
 * left untouched, keeping the linked profile intact.
 *
 * Idempotent (scoped by the old value) so it applies cleanly on every boot —
 * the deploy entrypoint runs `migrate` each time.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('user_type', 'artist')->update(['user_type' => 'both']);
    }

    public function down(): void
    {
        // Best-effort reverse: dual-app accounts holding the Artist role were the
        // former artists. Others keep 'both'.
        $artistUserIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'Artist')
            ->where('model_has_roles.model_type', \App\Models\User::class)
            ->pluck('model_has_roles.model_id');

        DB::table('users')
            ->where('user_type', 'both')
            ->whereIn('id', $artistUserIds)
            ->update(['user_type' => 'artist']);
    }
};
