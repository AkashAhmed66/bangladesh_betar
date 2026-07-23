<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LiveChannelResource;
use App\Models\BroadcastChannel;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public live-broadcasting API (M27): list the channels that are on air right
 * now and mint listener tokens so the public app can subscribe to the audio.
 */
class LiveController extends Controller
{
    public function __construct(private readonly LiveKitService $liveKit) {}

    /** Channels currently on air, most-listened first. */
    public function index(): JsonResponse
    {
        $channels = BroadcastChannel::query()
            ->live()
            ->where('is_active', true)
            ->with(['station', 'liveSession.broadcaster'])
            ->get()
            ->sortByDesc(fn (BroadcastChannel $c) => $c->liveSession?->current_listeners ?? 0)
            ->values();

        return LiveChannelResource::collection($channels)->response();
    }

    /** A single channel (whether or not it is live). */
    public function show(BroadcastChannel $broadcastChannel): JsonResponse
    {
        $broadcastChannel->load(['station', 'liveSession.broadcaster']);

        return response()->json([
            'data' => (new LiveChannelResource($broadcastChannel))->resolve(),
        ]);
    }

    /** Issue a subscribe-only LiveKit token for a currently-live channel. */
    public function token(Request $request, BroadcastChannel $broadcastChannel): JsonResponse
    {
        if (! $broadcastChannel->isLive()) {
            return response()->json(['message' => 'This channel is not live right now.'], 404);
        }

        if (! $this->liveKit->isConfigured()) {
            return response()->json(['message' => 'Live listening is not available.'], 503);
        }

        return response()->json(
            $this->liveKit->listenerToken($broadcastChannel, $request->user(), Str::random(12)),
        );
    }
}
