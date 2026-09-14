<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastChannel;
use App\Models\BroadcastSession;
use App\Models\Station;
use App\Services\ArtworkService;
use App\Services\BroadcastRecordingService;
use App\Services\LiveKitService;
use App\Services\SpeakRequestStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BroadcastChannelController extends Controller
{
    public function __construct(
        private readonly LiveKitService $liveKit,
        private readonly BroadcastRecordingService $recordings,
        private readonly ArtworkService $artwork,
    ) {}

    public function index(): View
    {
        $channels = BroadcastChannel::query()
            ->audio()
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
        $data['channel_type'] = 'audio';
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/live-radio', null);
        unset($data['artwork'], $data['remove_artwork']);

        BroadcastChannel::query()->create($data);

        return redirect()->route('admin.broadcast-channels.index')->with('success', 'Broadcast channel created.');
    }

    public function edit(BroadcastChannel $broadcastChannel): View
    {
        $this->ensureAudioChannel($broadcastChannel);
        $this->authorize('broadcasts.manage');

        return view('admin.broadcast-channels.form', ['channel' => $broadcastChannel] + $this->options());
    }

    public function update(Request $request, BroadcastChannel $broadcastChannel): RedirectResponse
    {
        $this->ensureAudioChannel($broadcastChannel);
        $this->authorize('broadcasts.manage');

        $data = $this->validated($request);
        $data['artwork_path'] = $this->artwork->sync($request, 'artwork/live-radio', $broadcastChannel->artwork_path);
        unset($data['artwork'], $data['remove_artwork']);

        $broadcastChannel->update($data);

        return redirect()->route('admin.broadcast-channels.index')->with('success', 'Broadcast channel updated.');
    }

    public function destroy(BroadcastChannel $broadcastChannel): RedirectResponse
    {
        $this->ensureAudioChannel($broadcastChannel);
        $this->authorize('broadcasts.manage');

        if ($broadcastChannel->isLive()) {
            return back()->with('error', 'Stop the live broadcast before deleting this channel.');
        }

        $broadcastChannel->delete();
        $this->artwork->delete($broadcastChannel->artwork_path);

        return redirect()->route('admin.broadcast-channels.index')->with('success', 'Broadcast channel deleted.');
    }

    /* --------------------------------------------------------------------- */
    /* Broadcaster studio (go on air) */
    /* --------------------------------------------------------------------- */

    public function studio(Request $request, BroadcastChannel $broadcastChannel): View
    {
        $this->ensureAudioChannel($broadcastChannel);
        $this->authorize('broadcasts.broadcast');

        $broadcastChannel->load(['station', 'liveSession.broadcaster', 'liveSession.recording']);

        // Link broadcasters to the listener-facing page. Derive the public app
        // origin from the request host (so it points at the same IP the studio
        // was opened on) unless PUBLIC_APP_URL is explicitly configured.
        $publicBase = trim((string) config('services.livekit.public_app_url'));
        if ($publicBase === '') {
            $publicBase = $request->getScheme().'://'.$request->getHost().':'.((int) config('services.livekit.public_app_port', 9000));
        }
        $listenUrl = rtrim($publicBase, '/').'/live/'.$broadcastChannel->id;

        $recordedSessions = $broadcastChannel->sessions()
            ->whereHas('recording')
            ->with(['broadcaster', 'recording'])
            ->paginate(10, ['*'], 'recordings')
            ->withQueryString();

        return view('admin.broadcast-channels.studio', [
            'channel' => $broadcastChannel,
            'wsUrl' => $this->liveKit->wsUrl($request->getHost()),
            'listenUrl' => $listenUrl,
            'recordedSessions' => $recordedSessions,
        ]);
    }

    /** Begin (or resume) an on-air session and hand back a publisher token. */
    public function goLive(Request $request, BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->ensureAudioChannel($broadcastChannel);
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

        $recording = $this->recordings->start($session);

        return response()->json(
            $this->liveKit->publisherToken($broadcastChannel, $user) + [
                'session_id' => $session->id,
                'recording_status' => $recording->status,
                'recording_error' => $recording->error,
            ],
        );
    }

    /** End the current on-air session. */
    public function stop(BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->ensureAudioChannel($broadcastChannel);
        $this->authorize('broadcasts.broadcast');

        $liveSessions = $broadcastChannel->sessions()
            ->where('status', 'live')
            ->with('recording')
            ->get();

        $recordingStopping = $liveSessions
            ->map(fn (BroadcastSession $session): bool => $this->recordings->stop($session))
            ->every(fn (bool $stopped): bool => $stopped);

        $ended = $broadcastChannel->sessions()->where('status', 'live')->update([
            'status' => 'ended',
            'ended_at' => now(),
            'current_listeners' => 0,
        ]);
        SpeakRequestStore::clear($broadcastChannel->room_name);

        // A session is not fully stopped while its LiveKit room remains open:
        // listeners can stay connected and the room can continue emitting
        // events. Delete the room to disconnect everyone immediately.
        $roomTerminated = $this->liveKit->terminateRoom($broadcastChannel->room_name);

        if (! $roomTerminated) {
            return response()->json([
                'message' => 'The broadcast session was ended, but LiveKit could not close the room. Check the LiveKit service and stop again.',
                'session_ended' => $ended > 0,
                'room_terminated' => false,
                'recording_finalizing' => $recordingStopping,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'session_ended' => $ended > 0,
            'room_terminated' => true,
            'recording_finalizing' => $recordingStopping,
        ]);
    }

    /** Lightweight polling endpoint for the studio (live state + listeners). */
    public function status(BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->ensureAudioChannel($broadcastChannel);
        $session = $broadcastChannel->liveSession()->with('recording')->first();

        return response()->json([
            'is_live' => $session !== null,
            'listeners' => $session?->current_listeners ?? 0,
            'peak_listeners' => $session?->peak_listeners ?? 0,
            'started_at' => $session?->started_at?->toIso8601String(),
            'recording_status' => $session?->recording?->status,
        ]);
    }

    /* --------------------------------------------------------------------- */
    /* Interactive audience: raise-hand / grant / revoke speaking */
    /* --------------------------------------------------------------------- */

    /** Current listeners in the room, flagged with raised-hand / speaker state. */
    public function participants(BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->ensureAudioChannel($broadcastChannel);
        $room = $broadcastChannel->room_name;
        $hands = SpeakRequestStore::all($room);

        $participants = collect($this->liveKit->listParticipants($room))
            ->map(function (array $p) use ($hands): array {
                $p['raised_hand'] = isset($hands[$p['identity']]);

                return $p;
            })
            // Raised hands first, then live speakers, then everyone else.
            ->sortByDesc(fn (array $p): int => ($p['raised_hand'] ? 4 : 0) + ($p['is_speaking'] ? 2 : 0) + ($p['can_publish'] ? 1 : 0))
            ->values();

        return response()->json(['participants' => $participants]);
    }

    /** Let a listener speak (canPublish = true). */
    public function grantSpeak(Request $request, BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->ensureAudioChannel($broadcastChannel);
        $identity = $request->string('identity')->trim()->toString();
        if ($identity === '') {
            return response()->json(['message' => 'Missing participant identity.'], 422);
        }

        $ok = $this->liveKit->setPublishPermission($broadcastChannel->room_name, $identity, true);
        SpeakRequestStore::remove($broadcastChannel->room_name, $identity);

        return response()->json(['ok' => $ok], $ok ? 200 : 502);
    }

    /** Revoke a listener's speaking access (canPublish = false; auto-unpublishes). */
    public function revokeSpeak(Request $request, BroadcastChannel $broadcastChannel): JsonResponse
    {
        $this->ensureAudioChannel($broadcastChannel);
        $identity = $request->string('identity')->trim()->toString();
        if ($identity === '') {
            return response()->json(['message' => 'Missing participant identity.'], 422);
        }

        $ok = $this->liveKit->setPublishPermission($broadcastChannel->room_name, $identity, false);
        SpeakRequestStore::remove($broadcastChannel->room_name, $identity);

        return response()->json(['ok' => $ok], $ok ? 200 : 502);
    }

    /* --------------------------------------------------------------------- */

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],
            'station_id' => ['required', 'integer', 'exists:stations,id'],
            'artwork' => ArtworkService::rules(),
            'remove_artwork' => ['boolean'],
            'is_active' => ['boolean'],
        ], [], ['station_id' => 'station']);
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

    private function ensureAudioChannel(BroadcastChannel $channel): void
    {
        abort_unless($channel->isAudio(), 404);
    }
}
