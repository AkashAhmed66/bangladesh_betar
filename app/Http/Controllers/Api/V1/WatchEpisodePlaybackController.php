<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WatchEpisode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WatchEpisodePlaybackController extends Controller
{
    public function __invoke(Request $request, WatchEpisode $watchEpisode): BinaryFileResponse
    {
        $watchEpisode->loadMissing('show');
        $adminPreview = $request->query('access') === 'admin';

        abort_unless(
            $adminPreview || ($watchEpisode->is_published && $watchEpisode->show?->is_published),
            404,
        );

        $disk = Storage::disk('public');
        abort_unless($watchEpisode->video_path && $disk->exists($watchEpisode->video_path), 404, 'Video not available.');

        return response()->file($disk->path($watchEpisode->video_path), [
            'Content-Type' => $disk->mimeType($watchEpisode->video_path) ?: 'video/mp4',
            'Content-Disposition' => 'inline',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
