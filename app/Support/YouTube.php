<?php

declare(strict_types=1);

namespace App\Support;

final class YouTube
{
    public static function videoId(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        $host = preg_replace('/^(www\.|m\.)/', '', $host) ?: $host;
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = explode('/', $path)[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if ($path === 'watch') {
                parse_str((string) ($parts['query'] ?? ''), $query);
                $videoId = is_string($query['v'] ?? null) ? $query['v'] : null;
            } elseif (preg_match('#^(?:embed|shorts|live)/([^/]+)#', $path, $matches) === 1) {
                $videoId = $matches[1];
            }
        }

        if (! is_string($videoId) || preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) !== 1) {
            return null;
        }

        return $videoId;
    }

    public static function canonicalUrl(string $url): ?string
    {
        $videoId = self::videoId($url);

        return $videoId ? "https://www.youtube.com/watch?v={$videoId}" : null;
    }

    public static function embedUrl(string $url): ?string
    {
        $videoId = self::videoId($url);

        return $videoId ? "https://www.youtube-nocookie.com/embed/{$videoId}" : null;
    }
}
