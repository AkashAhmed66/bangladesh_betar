<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['news_articles', 'watch_shows'] as $table) {
            if (! Schema::hasColumn($table, 'approval_status')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->string('approval_status', 32)->default('draft')->index();
                });
            }

            // Preserve existing public content during rollout. Every future
            // publish is still forced through the new approval endpoints.
            DB::table($table)->where('is_published', true)->update(['approval_status' => 'approved']);
        }

        $newsPublish = Permission::findOrCreate('news.publish', 'web');
        $watchPublish = Permission::findOrCreate('watch.publish', 'web');

        Role::query()->whereIn('name', ['Super Administrator', 'Archive Administrator', 'Content Curator', 'Approver'])
            ->get()->each(fn (Role $role) => $role->givePermissionTo($newsPublish));
        Role::query()->whereIn('name', ['Super Administrator', 'Archive Administrator', 'Content Curator', 'Programme Producer', 'Approver'])
            ->get()->each(fn (Role $role) => $role->givePermissionTo($watchPublish));

        foreach ([
            'news_article' => 'News Article Publication Workflow',
            'watch_show' => 'Watch Publication Workflow',
        ] as $contentType => $name) {
            $workflow = \App\Models\Workflow::query()->updateOrCreate(
                ['content_type' => $contentType],
                ['name' => $name, 'is_active' => true, 'escalation_hours' => 72],
            );
            \App\Models\WorkflowStage::query()->updateOrCreate(
                ['workflow_id' => $workflow->id, 'sequence' => 1],
                ['name' => 'Management Approval', 'approver_role' => 'Approver'],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['news_articles', 'watch_shows'] as $table) {
            if (Schema::hasColumn($table, 'approval_status')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('approval_status'));
            }
        }

        Permission::query()->whereIn('name', ['news.publish', 'watch.publish'])->delete();
        \App\Models\Workflow::query()->whereIn('content_type', ['news_article', 'watch_show'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
