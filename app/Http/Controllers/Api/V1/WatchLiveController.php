<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WatchLiveChannelResource;
use App\Models\BroadcastChannel;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class WatchLiveController extends Controller
{
    public function __construct(private readonly LiveKitService $liveKit) {}

    /** Active video channels, with channels currently on air first. */
    public function index(): JsonResponse
    {
        $channels = BroadcastChannel::query()
            ->video()
            ->where('is_active', true)
            ->with(['station', 'liveSession.broadcaster'])
            ->orderBy('name')
            ->get()
            ->sortByDesc(fn (BroadcastChannel $channel): int => $channel->isLive() ? 1 : 0)
            ->values();

        return WatchLiveChannelResource::collection($channels)->response();
    }

    public function show(BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->ensurePublicVideoChannel($broadcastChannel);
        $broadcastChannel->load(['station', 'liveSession.broadcaster']);

        return response()->json([
            'data' => (new WatchLiveChannelResource($broadcastChannel))->resolve(),
        ]);
    }

    /** Issue a subscribe-only token. Viewers can never publish tracks. */
    public function token(Request $request, BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->ensurePublicVideoChannel($broadcastChannel);

        if (! $broadcastChannel->isLive()) {
            return response()->json(['message' => 'This video channel is not live right now.'], 404);
        }

        if (! $this->liveKit->isConfigured()) {
            return response()->json(['message' => 'Live video is not available.'], 503);
        }

        return response()->json(
            $this->liveKit->listenerToken($broadcastChannel, $request->user(), Str::random(12)),
        );
    }

    private function ensurePublicVideoChannel(BroadcastChannel $channel): void
    {
        abort_unless($channel->isVideo() && $channel->is_active, 404);
    }
}
