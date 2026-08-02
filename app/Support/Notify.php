<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Notifications\AdminNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Thin sender for admin notifications. Recipient helpers resolve active
 * users by role or permission; the acting user can be excluded so people
 * are never notified about their own actions. Failures are logged, never
 * thrown — a notification must not break the action that triggered it.
 */
final class Notify
{
    public static function user(?User $user, string $event, string $title, string $message, ?string $url = null): void
    {
        if ($user) {
            self::send(collect([$user]), $event, $title, $message, $url);
        }
    }

    /** @param  iterable<User>  $users */
    public static function users(iterable $users, string $event, string $title, string $message, ?string $url = null, ?int $except = null): void
    {
        self::send(collect($users), $event, $title, $message, $url, $except);
    }

    /** Every active user holding the given role. */
    public static function role(string $role, string $event, string $title, string $message, ?string $url = null, ?int $except = null): void
    {
        try {
            $users = User::role($role)->where('status', 'active')->get();
        } catch (\Throwable $e) {
            Log::warning('[notify] unknown role', ['role' => $role, 'error' => $e->getMessage()]);

            return;
        }

        self::send($users, $event, $title, $message, $url, $except);
    }

    /** Every active user holding the given permission (directly or via role). */
    public static function permission(string $permission, string $event, string $title, string $message, ?string $url = null, ?int $except = null): void
    {
        try {
            $users = User::permission($permission)->where('status', 'active')->get();
        } catch (\Throwable $e) {
            Log::warning('[notify] unknown permission', ['permission' => $permission, 'error' => $e->getMessage()]);

            return;
        }

        self::send($users, $event, $title, $message, $url, $except);
    }

    /** @param  Collection<int, User>  $users */
    private static function send(Collection $users, string $event, string $title, string $message, ?string $url, ?int $except = null): void
    {
        try {
            $users->unique('id')
                ->reject(fn (User $u) => $except !== null && $u->id === $except)
                ->each(fn (User $u) => $u->notify(new AdminNotification($event, $title, $message, $url)));
        } catch (\Throwable $e) {
            Log::warning('[notify] failed to send', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }
}
