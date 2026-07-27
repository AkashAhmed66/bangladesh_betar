<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks "raise hand" (request-to-speak) signals from live listeners, keyed by
 * LiveKit room. This is transient app state (not a LiveKit concept): a listener
 * asks to speak, the broadcaster sees the raised hand in the studio, and it is
 * cleared once granted/revoked or when the listener leaves.
 */
class SpeakRequestStore
{
    private const TTL_SECONDS = 3600;

    private static function key(string $room): string
    {
        return "live:hands:{$room}";
    }

    /**
     * @return array<string, array{name: string, at: int}> identity => data
     */
    public static function all(string $room): array
    {
        return Cache::get(self::key($room), []);
    }

    public static function add(string $room, string $identity, string $name): void
    {
        if ($room === '' || $identity === '') {
            return;
        }
        $hands = self::all($room);
        $hands[$identity] = ['name' => $name, 'at' => time()];
        Cache::put(self::key($room), $hands, self::TTL_SECONDS);
    }

    public static function remove(string $room, string $identity): void
    {
        $hands = self::all($room);
        if (! isset($hands[$identity])) {
            return;
        }
        unset($hands[$identity]);
        Cache::put(self::key($room), $hands, self::TTL_SECONDS);
    }

    public static function clear(string $room): void
    {
        Cache::forget(self::key($room));
    }
}
