<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BroadcastSession;
use App\Services\LiveKitService;
use App\Services\SpeakRequestStore;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Receives LiveKit server webhooks so live sessions self-heal (M27):
 *   - room_finished       → the room emptied; end the session
 *   - participant_joined  → a listener tuned in; bump the live listener count
 *   - participant_left    → a listener left; drop the count
 *
 * The listener count is derived from participant identities (LiveKit's webhook
 * room snapshot does not carry a reliable participant total). Broadcasters use
 * a "broadcaster-*" identity and are excluded from the count. room_finished
 * resets the count to zero, so any drift from missed events self-corrects.
 *
 * The request is authenticated by a JWT in the Authorization header signed with
 * the LiveKit API secret, whose sha256 claim must match the raw body.
 */
class LiveKitWebhookController extends Controller
{
    public function __construct(private readonly LiveKitService $liveKit) {}

    public function handle(Request $request): Response
    {
        $raw = $request->getContent();

        if (! $this->liveKit->verifyWebhook($raw, $request->header('Authorization'))) {
            return response('invalid signature', 403);
        }

        $data = json_decode($raw, true) ?: [];
        $event = $data['event'] ?? null;
        $roomName = $data['room']['name'] ?? null;

        if (! $roomName) {
            return response('ok');
        }

        $session = BroadcastSession::query()
            ->where('room_name', $roomName)
            ->where('status', 'live')
            ->latest('started_at')
            ->first();

        if (! $session) {
            return response('ok');
        }

        switch ($event) {
            case 'room_finished':
                $session->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'current_listeners' => 0,
                ]);
                SpeakRequestStore::clear($roomName);
                break;

            case 'participant_joined':
                if ($this->isListener($data)) {
                    $session->increment('current_listeners');
                    $session->update([
                        'peak_listeners' => max($session->peak_listeners, $session->current_listeners),
                    ]);
                }
                break;

            case 'participant_left':
                if ($this->isListener($data)) {
                    $session->decrement('current_listeners');
                    if ($session->current_listeners < 0) {
                        $session->update(['current_listeners' => 0]);
                    }
                    // Drop any pending raise-hand from the departed listener.
                    if (($identity = $data['participant']['identity'] ?? '') !== '') {
                        SpeakRequestStore::remove($roomName, $identity);
                    }
                }
                break;
        }

        return response('ok');
    }

    /** A participant is a listener unless it is the broadcaster's publisher identity. */
    private function isListener(array $data): bool
    {
        $identity = $data['participant']['identity'] ?? '';

        return $identity !== '' && ! str_starts_with($identity, 'broadcaster-');
    }
}
