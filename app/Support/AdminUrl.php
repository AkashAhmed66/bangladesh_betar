<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Keeps admin-portal navigation on the origin that handled the current request.
 * Notification jobs may run outside HTTP and must not persist APP_URL hosts or
 * ports that can later become stale.
 */
final class AdminUrl
{
    public static function relative(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (str_starts_with($url, '//')) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '' || ! str_starts_with($path, '/admin')) {
            return null;
        }

        $relative = $path;
        if (isset($parts['query']) && $parts['query'] !== '') {
            $relative .= '?'.$parts['query'];
        }
        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $relative .= '#'.$parts['fragment'];
        }

        return $relative;
    }
}
