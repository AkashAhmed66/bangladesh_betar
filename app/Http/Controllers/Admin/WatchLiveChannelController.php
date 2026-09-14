<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastChannel;
use App\Models\BroadcastSession;
use App\Models\Station;
use App\Services\ArtworkService;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class WatchLiveChannelController extends Controller
{
    public function __construct(
        private readonly LiveKitService $liveKit,
        private readonly ArtworkService $artwork,
    ) {}

    public function index(): View
    {
        $channels = BroadcastChannel::query()
            ->video()
            ->with(['station', 'liveSession.broadcaster'])
            ->orderBy('name')
            ->get();

        return view('admin.watch-live-channels.index', compact('channels'));
    }

    public function create(): View
    {
        $this->authorize('watch.manage');

        return view('admin.watch-live-channels.form', ['channel' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('watch.manage');
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['room_name'] = 'betar-watch-'.Str::lower(Str::random(12));
        $data['channel_type'] = 'video';
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/watch-live', null);
        unset($data['artwork'], $data['remove_artwork']);

        BroadcastChannel::query()->create($data);

        return redirect()->route('admin.watch-live-channels.index')->with('success', 'Watch Live channel created.');
    }

    public function edit(BroadcastChannel $watchLiveChannel): View
    {
        $this->ensureVideoChannel($watchLiveChannel);
        $this->authorize('watch.manage');

        return view('admin.watch-live-channels.form', ['channel' => $watchLiveChannel] + $this->options());
    }

    public function update(Request $request, BroadcastChannel $watchLiveChannel): RedirectResponse
    {
        $this->ensureVideoChannel($watchLiveChannel);
        $this->authorize('watch.manage');
        $data = $this->validated($request);
        $data['artwork_path'] = $this->artwork->sync(
            $request,
            'artwork/watch-live',
            $watchLiveChannel->artwork_path,
        );
        unset($data['artwork'], $data['remove_artwork']);
        $watchLiveChannel->update($data);

        return redirect()->route('admin.watch-live-channels.index')->with('success', 'Watch Live channel updated.');
    }

    public function destroy(BroadcastChannel $watchLiveChannel): RedirectResponse
    {
        $this->ensureVideoChannel($watchLiveChannel);
        $this->authorize('watch.manage');

        if ($watchLiveChannel->isLive()) {
            return back()->with('error', 'Stop the live video before deleting this channel.');
        }

        $artwork = $watchLiveChannel->artwork_path;
        $watchLiveChannel->delete();
        $this->artwork->delete($artwork);

        return redirect()->route('admin.watch-live-channels.index')->with('success', 'Watch Live channel deleted.');
    }

    public function studio(Request $request, BroadcastChannel $watchLiveChannel): View
    {
        $this->ensureVideoChannel($watchLiveChannel);
        $this->authorize('watch.broadcast');
        $watchLiveChannel->load(['station', 'liveSession.broadcaster']);

        $publicBase = trim((string) config('services.livekit.public_app_url'));
        if ($publicBase === '') {
            $publicBase = $request->getScheme().'://'.$request->getHost().':'.((int) config('services.livekit.public_app_port', 15001));
        }

        return view('admin.watch-live-channels.studio', [
            'channel' => $watchLiveChannel,
            'wsUrl' => $this->liveKit->wsUrl($request->getHost()),
            'watchUrl' => rtrim($publicBase, '/').'/watch/live/'.$watchLiveChannel->id,
        ]);
    }

    public function goLive(Request $request, BroadcastChannel $watchLiveChannel): JsonResponse
    {
        $this->ensureVideoChannel($watchLiveChannel);
        $this->authorize('watch.broadcast');

        if (! $watchLiveChannel->is_active) {
            return response()->json(['message' => 'This Watch channel is disabled.'], 422);
        }

        if (! $this->liveKit->isConfigured()) {
            return response()->json(['message' => 'Live video is not configured on the server.'], 503);
        }

        $data = $request->validate(['title' => ['nullable', 'string', 'max:120']]);
        $user = $request->user();
        $existing = $watchLiveChannel->liveSession()->first();

        if ($existing && $existing->broadcaster_id !== $user->id) {
            return response()->json(['message' => 'This channel is already live with another presenter.'], 409);
        }

        $session = $existing ?? BroadcastSession::query()->create([
            'broadcast_channel_id' => $watchLiveChannel->id,
            'broadcaster_id' => $user->id,
            'room_name' => $watchLiveChannel->room_name,
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'status' => 'live',
            'started_at' => now(),
        ]);

        return response()->json(
            $this->liveKit->publisherToken($watchLiveChannel, $user) + ['session_id' => $session->id],
        );
    }

    public function stop(BroadcastChannel $watchLiveChannel): JsonResponse
    {
        $this->ensureVideoChannel($watchLiveChannel);
        $this->authorize('watch.broadcast');

        $ended = $watchLiveChannel->sessions()->where('status', 'live')->update([
            'status' => 'ended',
            'ended_at' => now(),
            'current_listeners' => 0,
        ]);
        $terminated = $this->liveKit->terminateRoom($watchLiveChannel->room_name);

        if (! $terminated) {
            return response()->json([
                'message' => 'The video session ended, but LiveKit could not close the room. Check the LiveKit service and stop again.',
                'session_ended' => $ended > 0,
                'room_terminated' => false,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'session_ended' => $ended > 0,
            'room_terminated' => true,
        ]);
    }

    public function status(BroadcastChannel $watchLiveChannel): JsonResponse
    {
        $this->ensureVideoChannel($watchLiveChannel);
        $session = $watchLiveChannel->liveSession()->first();

        return response()->json([
            'is_live' => $session !== null,
            'viewers' => $session?->current_listeners ?? 0,
            'peak_viewers' => $session?->peak_listeners ?? 0,
            'started_at' => $session?->started_at?->toIso8601String(),
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'description_bn' => ['nullable', 'string', 'max:2000'],
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'artwork' => ArtworkService::rules(),
            'remove_artwork' => ['boolean'],
            'is_active' => ['boolean'],
        ], [], ['station_id' => 'station']);
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return ['stations' => Station::query()->orderBy('name')->pluck('name', 'id')];
    }

    private function uniqueSlug(string $name): string
    {
        $base = 'watch-'.(Str::slug($name) ?: 'channel');
        $slug = $base;
        $number = 2;

        while (BroadcastChannel::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$number++;
        }

        return $slug;
    }

    private function ensureVideoChannel(BroadcastChannel $channel): void
    {
        abort_unless($channel->isVideo(), 404);
    }
}
