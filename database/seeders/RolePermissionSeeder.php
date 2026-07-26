<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * M01 — Roles & granular permissions (FR-USR-03).
 *
 * Every admin-portal action is gated by one of these permissions.
 * Roles mirror the FRS user classes (§2.2).
 */
class RolePermissionSeeder extends Seeder
{
    /** Permission groups: [group => [action, ...]] */
    private const PERMISSIONS = [
        'dashboard' => ['view'],
        'users' => ['view', 'create', 'edit', 'delete'],
        'roles' => ['view', 'manage'],
        'stations' => ['view', 'manage'],
        'taxonomies' => ['view', 'manage'],            // categories, genres, moods, languages, tags
        'artists' => ['view', 'manage'],
        'assets' => ['view', 'upload', 'edit', 'delete', 'publish', 'download', 'export'],
        'versions' => ['view', 'manage'],
        'digitization' => ['view', 'manage'],
        'metadata' => ['edit', 'bulk-edit'],
        'albums' => ['view', 'manage'],
        'songs' => ['view', 'manage'],
        'programmes' => ['view', 'manage'],
        'episodes' => ['view', 'manage'],
        'podcasts' => ['view', 'manage'],
        'editing' => ['view', 'use'],
        'workflows' => ['view', 'manage'],
        'approvals' => ['view', 'act'],
        'rights' => ['view', 'manage'],
        'ai-moderation' => ['view', 'review'],          // duplicate / violence / anti-government gate + transcript (M16)
        'broadcasts' => ['view', 'manage', 'broadcast'], // live audio channels: view/manage config, broadcast = go on air (M27)
        'playlists' => ['view', 'manage'],             // editorial playlists / curated collections
        'curation' => ['view', 'manage'],              // home sections, banners, featured content
        'moderation' => ['view', 'manage'],            // comments + Community Inbox (reports, issues, feedback)
        'ads' => ['view', 'manage', 'reports'],
        'plans' => ['view', 'manage'],
        'subscriptions' => ['view', 'manage'],
        'payments' => ['view', 'refund'],
        'audit' => ['view'],
        'backups' => ['view', 'manage'],
        'settings' => ['view', 'manage'],
        'notifications' => ['view', 'send'],
    ];

    /** Role => permission spec ('*' = everything, 'group.*' = whole group). */
    private const ROLES = [
        'Super Administrator' => ['*'],

        'Archive Administrator' => [
            'dashboard.view', 'stations.*', 'taxonomies.*', 'artists.*',
            'assets.*', 'versions.*', 'digitization.*', 'metadata.*',
            'albums.view', 'songs.view', 'programmes.*', 'episodes.*',
            'workflows.view', 'approvals.*', 'rights.view',
            'ai-moderation.*', 'broadcasts.*', 'audit.view', 'backups.*',
            'moderation.*', 'notifications.view',
        ],

        'AI Reviewer' => [ // Sign-off gate for AI-flagged duplicate / violence / anti-government content
            'dashboard.view', 'ai-moderation.*', 'assets.view', 'notifications.view',
        ],

        'Broadcaster' => [ // Goes on air with live audio channels (M27)
            'dashboard.view', 'broadcasts.view', 'broadcasts.broadcast',
            'assets.view', 'notifications.view',
        ],

        'Archivist' => [ // Audio Archivist / Digitization Operator
            'dashboard.view', 'assets.view', 'assets.upload', 'assets.edit', 'assets.download',
            'versions.view', 'digitization.*', 'metadata.edit', 'metadata.bulk-edit',
            'artists.view', 'taxonomies.view', 'programmes.view', 'episodes.view',
            'notifications.view',
        ],

        'Audio Editor' => [
            'dashboard.view', 'assets.view', 'versions.view', 'versions.manage',
            'editing.*', 'notifications.view',
        ],

        'Programme Producer' => [
            'dashboard.view', 'programmes.*', 'episodes.*',
            'assets.view', 'assets.upload', 'playlists.view', 'playlists.manage',
            'broadcasts.view', 'broadcasts.broadcast',
            'approvals.view', 'notifications.view',
        ],

        'Podcast Manager' => [
            'dashboard.view', 'podcasts.*', 'assets.view', 'assets.upload',
            'artists.view', 'approvals.view', 'notifications.view',
        ],

        'Music Library Manager' => [
            'dashboard.view', 'songs.*', 'albums.*', 'artists.*', 'assets.view', 'assets.upload',
            'metadata.edit', 'playlists.*', 'taxonomies.view', 'notifications.view',
        ],

        'Content Curator' => [
            'dashboard.view', 'curation.*', 'playlists.*', 'assets.view',
            'songs.view', 'albums.view', 'artists.view', 'podcasts.view',
            'programmes.view', 'episodes.view', 'notifications.view',
        ],

        'Moderator' => [
            'dashboard.view', 'moderation.*',
            'users.view', 'notifications.view',
        ],

        'Advertisement Manager' => [
            'dashboard.view', 'ads.*', 'approvals.view', 'notifications.view',
        ],

        'Copyright Officer' => [
            'dashboard.view', 'rights.*', 'assets.view',
            'approvals.view', 'approvals.act', 'audit.view', 'notifications.view',
        ],

        'Approver' => [ // Management
            'dashboard.view', 'approvals.*', 'workflows.view', 'assets.view', 'assets.publish',
            'subscriptions.view', 'payments.view', 'ads.reports', 'notifications.view',
        ],

        'Researcher' => [
            'dashboard.view', 'assets.view', 'programmes.view', 'episodes.view',
            'songs.view', 'albums.view', 'artists.view', 'podcasts.view',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $all = [];
        foreach (self::PERMISSIONS as $group => $actions) {
            foreach ($actions as $action) {
                $name = "$group.$action";
                Permission::findOrCreate($name, 'web');
                $all[] = $name;
            }
        }

        // The registrar cache was primed during findOrCreate calls above;
        // reset it so role syncing can resolve the newly created permissions.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::ROLES as $roleName => $spec) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($this->expand($spec, $all));
        }

        // Public listener role — no admin permissions, used by the API guard.
        Role::findOrCreate('Listener', 'web');

        $this->command?->info('Roles: '.count(self::ROLES).' + Listener, permissions: '.count($all));
    }

    /** @param list<string> $spec @param list<string> $all @return list<string> */
    private function expand(array $spec, array $all): array
    {
        $result = [];
        foreach ($spec as $entry) {
            if ($entry === '*') {
                return $all;
            }
            if (str_ends_with($entry, '.*')) {
                $group = substr($entry, 0, -2);
                $result = array_merge($result, array_values(array_filter($all, fn (string $p) => str_starts_with($p, "$group."))));
            } else {
                $result[] = $entry;
            }
        }

        return array_values(array_unique($result));
    }
}
