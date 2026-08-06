<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Hls;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Encrypted-HLS delivery (download protection). All three endpoints are
 * behind the `signed` middleware — the playlist URL is only ever minted in
 * an authorized context (stream descriptor, premium audio-book detail,
 * admin pages), and it in turn mints short-lived signed segment/key URLs.
 * Segments are additionally rate-limited so the archive cannot be bulk-
 * ripped even with valid URLs.
 */
class HlsController extends Controller
{
    /**
     * The stored playlist references bare segment names and a __KEY_URI__
     * placeholder; every response rewrites them into signed URLs. Segment
     * URLs must outlive the whole listen, so their TTL scales with length.
     */
    public function playlist(Request $request, string $group, int $id, string $variant): Response
    {
        $this->validatePath($group, $variant);

        $disk = Storage::disk('local');
        $rel = Hls::dir($group, $id, $variant).'/index.m3u8';
        abort_unless($disk->exists($rel), 404, 'Stream not available.');

        $lines = preg_split('/\r?\n/', (string) $disk->get($rel)) ?: [];
        $segments = count(array_filter($lines, fn ($l) => $l !== '' && ! str_starts_with($l, '#')));
        // ~10s per segment, doubled, +15 min slack — survives pauses/seeks.
        $segmentTtl = now()->addMinutes(max(60, (int) ceil($segments * 20 / 60) + 15));
        $keyTtl = now()->addMinutes(30);

        $out = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '#EXT-X-KEY')) {
                $keyUrl = URL::temporarySignedRoute('api.v1.hls.key', $keyTtl, [
                    'group' => $group, 'id' => $id, 'variant' => $variant,
                ]);
                $out[] = str_replace('__KEY_URI__', $keyUrl, $line);
            } elseif ($line !== '' && ! str_starts_with($line, '#')) {
                $out[] = URL::temporarySignedRoute('api.v1.hls.segment', $segmentTtl, [
                    'group' => $group, 'id' => $id, 'variant' => $variant, 'file' => trim($line),
                ]);
            } else {
                $out[] = $line;
            }
        }

        return response(implode("\n", $out), 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** The 16-byte AES key — signed, short-lived, never cached. */
    public function key(Request $request, string $group, int $id, string $variant): Response
    {
        $this->validatePath($group, $variant);

        $disk = Storage::disk('local');
        $rel = Hls::dir($group, $id, $variant).'/key.bin';
        abort_unless($disk->exists($rel), 404);

        return response($disk->get($rel), 200, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** One encrypted ~10s chunk. Throttled: bulk ripping is rate-capped. */
    public function segment(Request $request, string $group, int $id, string $variant, string $file): BinaryFileResponse
    {
        $this->validatePath($group, $variant);
        abort_unless(preg_match('/^seg\d+\.ts$/', $file) === 1, 404);

        $disk = Storage::disk('local');
        $rel = Hls::dir($group, $id, $variant).'/'.$file;
        abort_unless($disk->exists($rel), 404);

        return response()->file($disk->path($rel), [
            'Content-Type' => 'video/MP2T',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validatePath(string $group, string $variant): void
    {
        abort_unless(in_array($group, Hls::GROUPS, true), 404);
        abort_unless(preg_match('/^[a-z]+$/', $variant) === 1, 404);
    }
}
