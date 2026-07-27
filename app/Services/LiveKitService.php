<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BroadcastChannel;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LiveKit integration for live audio broadcasting (M27).
 *
 * LiveKit access tokens are plain HS256 JWTs whose `video` claim carries the
 * grant. We mint them by hand (hash_hmac) so no extra composer dependency is
 * needed. The same secret verifies inbound webhook signatures.
 */
class LiveKitService
{
    private string $host;

    private string $apiKey;

    private string $apiSecret;

    public function __construct()
    {
        $this->host = rtrim((string) config('services.livekit.host'), '/');
        $this->apiKey = (string) config('services.livekit.api_key');
        $this->apiSecret = (string) config('services.livekit.api_secret');
    }

    /**
     * Browser-facing signalling URL handed to the client SDK.
     *
     * If LIVEKIT_WS_URL is configured it is used verbatim (production wss://
     * domain). Otherwise it is derived from the host the request came in on, so
     * a listener on 192.168.x.y gets ws://192.168.x.y:7880 and a local dev on
     * localhost gets ws://localhost:7880 — no per-machine rebuild needed.
     */
    public function wsUrl(?string $requestHost = null): string
    {
        $configured = trim((string) config('services.livekit.ws_url'));
        if ($configured !== '') {
            return $configured;
        }

        $host = $requestHost ?: (request()?->getHost() ?: 'localhost');

        return 'ws://'.$host.':'.((int) config('services.livekit.ws_port', 7880));
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->apiSecret !== '';
    }

    /**
     * Token allowing a broadcaster to publish (mic) into the channel's room.
     *
     * @return array{ws_url:string, token:string, room:string}
     */
    public function publisherToken(BroadcastChannel $channel, User $broadcaster): array
    {
        $ttl = (int) config('services.livekit.publisher_ttl_minutes', 360);

        return [
            'ws_url' => $this->wsUrl(),
            'room' => $channel->room_name,
            'token' => $this->mintToken(
                identity: 'broadcaster-'.$broadcaster->id,
                name: $broadcaster->name,
                room: $channel->room_name,
                canPublish: true,
                ttlMinutes: $ttl,
            ),
        ];
    }

    /**
     * Token allowing a listener to subscribe to (but not publish into) a room.
     * Guests get a stable-ish anonymous identity.
     *
     * @return array{ws_url:string, token:string, room:string}
     */
    public function listenerToken(BroadcastChannel $channel, ?User $user, string $anonId): array
    {
        $ttl = (int) config('services.livekit.listener_ttl_minutes', 180);

        // Identity must be unique per connection (LiveKit disconnects an older
        // participant sharing an identity), so always suffix a random token.
        $identity = ($user ? 'listener-'.$user->id.'-' : 'guest-').$anonId;
        $name = $user->name ?? 'Guest listener';

        return [
            'ws_url' => $this->wsUrl(),
            'room' => $channel->room_name,
            'token' => $this->mintToken(
                identity: $identity,
                name: $name,
                room: $channel->room_name,
                canPublish: false,
                ttlMinutes: $ttl,
            ),
        ];
    }

    /**
     * Verify an inbound LiveKit webhook. The Authorization header is a JWT
     * signed with the API secret whose `sha256` claim is the base64 (std)
     * SHA-256 of the raw request body.
     */
    public function verifyWebhook(string $rawBody, ?string $authHeader): bool
    {
        if ($authHeader === null || $this->apiSecret === '') {
            return false;
        }

        $token = trim(preg_replace('/^Bearer\s+/i', '', $authHeader) ?? '');
        $claims = $this->decodeToken($token);
        if ($claims === null || ! isset($claims['sha256'])) {
            return false;
        }

        // Optional but cheap: ensure it was issued by our key.
        if (isset($claims['iss']) && $claims['iss'] !== $this->apiKey) {
            return false;
        }

        // The signed body digest must match the raw payload we received.
        $expected = base64_encode(hash('sha256', $rawBody, true));

        return hash_equals((string) $claims['sha256'], $expected);
    }

    /**
     * Best-effort list of active rooms from the LiveKit server (Twirp). Used to
     * reconcile/annotate live state. Returns [roomName => numParticipants].
     *
     * @return array<string, int>
     */
    public function activeRoomParticipants(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $admin = $this->mintToken(
                identity: 'server-admin',
                name: 'server',
                room: '',
                canPublish: false,
                ttlMinutes: 5,
                extraVideoGrant: ['roomList' => true, 'roomAdmin' => true],
            );

            $res = Http::timeout(4)
                ->withToken($admin)
                ->acceptJson()
                ->post($this->host.'/twirp/livekit.RoomService/ListRooms', (object) []);

            if (! $res->successful()) {
                return [];
            }

            $out = [];
            foreach ((array) $res->json('rooms', []) as $room) {
                if (isset($room['name'])) {
                    $out[$room['name']] = (int) ($room['num_participants'] ?? $room['numParticipants'] ?? 0);
                }
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('LiveKit ListRooms failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Live participants in a room (RoomService/ListParticipants), normalised for
     * the studio's listener panel. The broadcaster and server identities are
     * excluded — only tune-in listeners are returned.
     *
     * @return list<array{identity:string, name:string, can_publish:bool, is_speaking:bool, joined_at:int}>
     */
    public function listParticipants(string $room): array
    {
        if (! $this->isConfigured() || $room === '') {
            return [];
        }

        try {
            $res = Http::timeout(4)
                ->withToken($this->roomAdminToken($room))
                ->acceptJson()
                ->post($this->host.'/twirp/livekit.RoomService/ListParticipants', ['room' => $room]);

            if (! $res->successful()) {
                return [];
            }

            $out = [];
            foreach ((array) $res->json('participants', []) as $p) {
                $identity = (string) ($p['identity'] ?? '');
                if ($identity === '' || $identity === 'server-admin' || str_starts_with($identity, 'broadcaster-')) {
                    continue;
                }

                $permission = (array) ($p['permission'] ?? []);
                $speaking = false;
                foreach ((array) ($p['tracks'] ?? []) as $t) {
                    if (($t['type'] ?? '') === 'AUDIO' && ! ($t['muted'] ?? false)) {
                        $speaking = true;
                    }
                }

                $out[] = [
                    'identity' => $identity,
                    'name' => (string) ($p['name'] ?? 'Listener'),
                    'can_publish' => (bool) ($permission['canPublish'] ?? $permission['can_publish'] ?? false),
                    'is_speaking' => $speaking,
                    'joined_at' => (int) ($p['joinedAt'] ?? $p['joined_at'] ?? 0),
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('LiveKit ListParticipants failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Grant or revoke a participant's permission to publish (speak) in a room
     * via RoomService/UpdateParticipant. LiveKit pushes the new permission to
     * the participant live — no reconnect — and revoking auto-unpublishes any
     * track they had open.
     */
    public function setPublishPermission(string $room, string $identity, bool $canPublish): bool
    {
        if (! $this->isConfigured() || $room === '' || $identity === '') {
            return false;
        }

        try {
            $res = Http::timeout(4)
                ->withToken($this->roomAdminToken($room))
                ->acceptJson()
                ->post($this->host.'/twirp/livekit.RoomService/UpdateParticipant', [
                    'room' => $room,
                    'identity' => $identity,
                    'permission' => [
                        'canSubscribe' => true,
                        'canPublish' => $canPublish,
                        'canPublishData' => $canPublish,
                    ],
                ]);

            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('LiveKit UpdateParticipant failed: '.$e->getMessage());

            return false;
        }
    }

    /* --------------------------------------------------------------------- */
    /* JWT helpers (HS256)                                                     */
    /* --------------------------------------------------------------------- */

    /**
     * @param  array<string, mixed>  $extraVideoGrant
     */
    private function mintToken(
        string $identity,
        string $name,
        string $room,
        bool $canPublish,
        int $ttlMinutes,
        array $extraVideoGrant = [],
    ): string {
        $now = time();

        $video = array_merge([
            'roomJoin' => true,
            'room' => $room,
            'canPublish' => $canPublish,
            'canSubscribe' => true,
            'canPublishData' => $canPublish,
        ], $extraVideoGrant);

        // A room-scoped join grant needs a room; server-admin grants don't.
        if ($room === '') {
            unset($video['roomJoin'], $video['room'], $video['canPublish'], $video['canSubscribe'], $video['canPublishData']);
        }

        $payload = [
            'exp' => $now + ($ttlMinutes * 60),
            'iss' => $this->apiKey,
            'nbf' => $now - 5,
            'sub' => $identity,
            'name' => $name,
            'video' => $video,
        ];

        return $this->encodeToken($payload);
    }

    /** A short-lived server token with roomAdmin rights over a single room. */
    private function roomAdminToken(string $room): string
    {
        return $this->mintToken(
            identity: 'server-admin',
            name: 'server',
            room: $room,
            canPublish: false,
            ttlMinutes: 5,
            extraVideoGrant: ['roomAdmin' => true],
        );
    }

    /** @param array<string, mixed> $payload */
    private function encodeToken(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $segments = [
            $this->b64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $this->apiSecret, true);
        $segments[] = $this->b64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Verify signature and return claims, or null if invalid.
     *
     * @return array<string, mixed>|null
     */
    private function decodeToken(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        [$h, $p, $s] = $parts;
        $expected = $this->b64UrlEncode(hash_hmac('sha256', "$h.$p", $this->apiSecret, true));
        if (! hash_equals($expected, $s)) {
            return null;
        }

        $claims = json_decode((string) $this->b64UrlDecode($p), true);

        return is_array($claims) ? $claims : null;
    }

    private function b64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
