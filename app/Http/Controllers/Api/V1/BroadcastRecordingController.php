<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BroadcastRecordingResource;
use App\Models\BroadcastRecording;
use App\Support\Hls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BroadcastRecordingController extends Controller
{
    /** Paginated metadata for published old broadcasts. */
    public function index(): JsonResponse
    {
        $recordings = BroadcastRecording::query()
            ->published()
            ->with(['session.channel.station', 'session.broadcaster'])
            ->latest('published_at')
            ->paginate(8)
            ->withQueryString();

        // Start preparing protected audio as soon as the archive is viewed so
        // a Premium listener normally finds it ready by the time they press play.
        $recordings->getCollection()->each(
            static fn (BroadcastRecording $recording): ?string => Hls::broadcastRecordingUrl($recording),
        );

        return BroadcastRecordingResource::collection($recordings)->response();
    }

    /** Mint a short-lived encrypted-HLS stream for a Premium listener. */
    public function stream(Request $request, BroadcastRecording $recording): JsonResponse
    {
        abort_unless($recording->isPublished(), 404, 'Broadcast recording not available.');
        abort_unless($request->user()?->isPremium(), 403, 'Old broadcasts are available to Premium listeners.');

        $url = Hls::broadcastRecordingUrl($recording);
        if ($url === null) {
            return response()->json([
                'message' => 'This protected recording is being prepared. Please try again in a moment.',
            ], 425);
        }

        return response()->json([
            'data' => [
                'url' => $url,
                'is_hls' => true,
                'expires_at' => now()->addMinutes(30)->toIso8601String(),
            ],
        ]);
    }
}
