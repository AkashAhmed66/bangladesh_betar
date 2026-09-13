<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LiveKitService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveKitServiceTest extends TestCase
{
    public function test_terminating_a_room_uses_the_required_room_create_grant(): void
    {
        config([
            'services.livekit.host' => 'http://livekit.test',
            'services.livekit.api_key' => 'test-key',
            'services.livekit.api_secret' => 'test-secret',
        ]);
        Http::fake([
            'http://livekit.test/twirp/livekit.RoomService/DeleteRoom' => Http::response([], 200),
        ]);

        $this->assertTrue((new LiveKitService)->terminateRoom('test-room'));

        Http::assertSent(function (Request $request): bool {
            $token = (string) $request->header('Authorization')[0];
            [, $payload] = explode('.', substr($token, 7));
            $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);

            return $request->url() === 'http://livekit.test/twirp/livekit.RoomService/DeleteRoom'
                && $request['room'] === 'test-room'
                && ($claims['video']['roomCreate'] ?? false) === true;
        });
    }

    public function test_starting_a_recording_uses_audio_only_ogg_egress_and_room_record_grant(): void
    {
        config([
            'services.livekit.host' => 'http://livekit.test',
            'services.livekit.api_key' => 'test-key',
            'services.livekit.api_secret' => 'test-secret',
        ]);
        Http::fake([
            'http://livekit.test/twirp/livekit.RoomService/CreateRoom' => Http::response(['name' => 'test-room'], 200),
            'http://livekit.test/twirp/livekit.Egress/StartRoomCompositeEgress' => Http::response([
                'egressId' => 'EG_test',
                'status' => 1,
            ], 200),
        ]);

        $service = new LiveKitService;
        $this->assertTrue($service->ensureRoom('test-room'));
        $this->assertSame('EG_test', $service->startRoomRecording('test-room', '/out/test.ogg')['egress_id']);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'http://livekit.test/twirp/livekit.Egress/StartRoomCompositeEgress') {
                return false;
            }

            $token = (string) $request->header('Authorization')[0];
            [, $payload] = explode('.', substr($token, 7));
            $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);

            return $request['roomName'] === 'test-room'
                && $request['audioOnly'] === true
                && $request['fileOutputs'][0]['fileType'] === 2
                && $request['fileOutputs'][0]['filepath'] === '/out/test.ogg'
                && ($claims['video']['roomRecord'] ?? false) === true;
        });
    }
}
