<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastChannel;
use App\Models\BroadcastSession;
use App\Models\Station;
use App\Services\LiveKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BroadcastChannelController extends Controller
{
    public function __construct(private readonly LiveKitService $liveKit) {}

    public function index(): View
    {
        $channels = BroadcastChannel::query()
            ->with(['station', 'liveSession.broadcaster'])
            ->orderBy('name')
            ->get();

        return view('admin.broadcast-channels.index', compact('channels'));
    }

    public function create(): View
    {
        $this->authorize('broadcasts.manage');

        return view('admin.broadcast-channels.form', ['channel' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('broadcasts.manage');

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['room_name'] = 'betar-'.Str::lower(Str::random(12));

        BroadcastChannel::query()->create($data);

        return redirect()->route('admin.broadcast-channels.index')->with('success', 'Broadcast channel created.');
    }

    public function edit(BroadcastChannel $broadcastChannel): View
    {
        $this->authorize('broadcasts.manage');

        return view('admin.broadcast-channels.form', ['channel' => $broadcastChannel] + $this->options());
    }

    public function update(Request $request, BroadcastChannel $broadcastChannel): RedirectResponse
    {
        $this->authorize('broadcasts.manage');

        $broadcastChannel->update($this->validated($request));

        return redirect()->route('admin.broadcast-channels.index')->with('success', 'Broadcast channel updated.');
    }

    public function destroy(BroadcastChannel $broadcastChannel): RedirectResponse
    {
        $this->authorize('broadcasts.manage');

        if ($broadcastChannel->isLive()) {
            return back()->with('error', 'Stop the live broadcast before deleting this channel.');
        }

        $broadcastChannel->delete();

        return redirect()->route('admin.broadcast-channels.index')->with('success', 'Broadcast channel deleted.');
    }

    /* --------------------------------------------------------------------- */
    /* Broadcaster studio (go on air)                                         */
    /* --------------------------------------------------------------------- */

    public function studio(Request $request, BroadcastChannel $broadcastChannel): View
    {
        $this->authorize('broadcasts.broadcast');

        $broadcastChannel->load(['station', 'liveSession.broadcaster']);

        // Link broadcasters to the listener-facing page. Derive the public app
        // origin from the request host (so it points at the same IP the studio
        // was opened on) unless PUBLIC_APP_URL is explicitly configured.
        $publicBase = trim((string) config('services.livekit.public_app_url'));
        if ($publicBase === '') {
            $publicBase = $request->getScheme().'://'.$request->getHost().':'.((int) config('services.livekit.public_app_port', 9000));
        }
        $listenUrl = rtrim($publicBase, '/').'/live/'.$broadcastChannel->id;

        $recentSessions = $broadcastChannel->sessions()
            ->where('status', 'ended')
            ->with('broadcaster')
            ->limit(6)
            ->get();

        return view('admin.broadcast-channels.studio', [
            'channel' => $broadcastChannel,
            'wsUrl' => $this->liveKit->wsUrl($request->getHost()),
            'listenUrl' => $listenUrl,
            'recentSessions' => $recentSessions,
        ]);
    }

    /** Begin (or resume) an on-air session and hand back a publisher token. */
    public function goLive(Request $request, BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->authorize('broadcasts.broadcast');

        if (! $broadcastChannel->is_active) {
            return response()->json(['message' => 'This channel is disabled and cannot go live.'], 422);
        }

        if (! $this->liveKit->isConfigured()) {
            return response()->json(['message' => 'Live broadcasting is not configured on the server.'], 503);
        }

        $user = $request->user();
        $existing = $broadcastChannel->liveSession()->first();

        if ($existing && $existing->broadcaster_id !== $user->id) {
            return response()->json([
                'message' => 'This channel is already live with another broadcaster.',
            ], 409);
        }

        $session = $existing ?? BroadcastSession::query()->create([
            'broadcast_channel_id' => $broadcastChannel->id,
            'broadcaster_id' => $user->id,
            'room_name' => $broadcastChannel->room_name,
            'title' => $request->string('title')->trim()->toString() ?: null,
            'status' => 'live',
            'started_at' => now(),
        ]);

        return response()->json(
            $this->liveKit->publisherToken($broadcastChannel, $user) + ['session_id' => $session->id],
        );
    }

    /** End the current on-air session. */
    public function stop(BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->authorize('broadcasts.broadcast');

        $broadcastChannel->sessions()->where('status', 'live')->update([
            'status' => 'ended',
            'ended_at' => now(),
            'current_listeners' => 0,
        ]);

        return response()->json(['ok' => true]);
    }

    /** Lightweight polling endpoint for the studio (live state + listeners). */
    public function status(BroadcastChannel $broadcastChannel): JsonResponse
    {
        $session = $broadcastChannel->liveSession()->first();

        return response()->json([
            'is_live' => $session !== null,
            'listeners' => $session?->current_listeners ?? 0,
            'peak_listeners' => $session?->peak_listeners ?? 0,
            'started_at' => $session?->started_at?->toIso8601String(),
        ]);
    }

    /* --------------------------------------------------------------------- */

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'is_active' => ['boolean'],
        ]);
    }

    private function options(): array
    {
        return [
            'stations' => Station::query()->orderBy('name')->pluck('name', 'id'),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'channel';
        $slug = $base;
        $i = 2;

        while (BroadcastChannel::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
