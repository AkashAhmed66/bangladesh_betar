<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\BroadcastRecording;
use App\Models\BroadcastSession;
use App\Models\User;
use App\Services\BroadcastRecordingService;
use App\Services\LiveKitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BroadcastRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_studio_lists_recording_metadata_and_serves_private_audio(): void
    {
        Storage::fake('broadcast_recordings');

        $user = $this->staffUser();
        $channel = $this->channel();
        $session = BroadcastSession::query()->create([
            'broadcast_channel_id' => $channel->id,
            'broadcaster_id' => $user->id,
            'room_name' => $channel->room_name,
            'title' => 'Evening News Bulletin',
            'status' => 'ended',
            'peak_listeners' => 17,
            'started_at' => now()->subSeconds(125),
            'ended_at' => now(),
        ]);
        $recording = BroadcastRecording::query()->create([
            'broadcast_session_id' => $session->id,
            'egress_id' => 'EG_test_recording',
            'status' => 'complete',
            'disk' => 'broadcast_recordings',
            'file_path' => 'channel-1/evening-news.ogg',
            'format' => 'ogg',
            'mime_type' => 'audio/ogg',
            'file_size' => 2048,
            'duration_seconds' => 125,
            'started_at' => $session->started_at,
            'ended_at' => $session->ended_at,
        ]);
        Storage::disk('broadcast_recordings')->put($recording->file_path, 'fake-ogg-audio');

        $this->actingAs($user)
            ->get(route('admin.broadcast-channels.studio', $channel))
            ->assertOk()
            ->assertSee('Broadcast recordings')
            ->assertSee('Evening News Bulletin')
            ->assertSee('02:05')
            ->assertSee('17')
            ->assertSee(route('admin.broadcast-recordings.audio', $recording), false)
            ->assertSee(route('admin.broadcast-recordings.download', $recording), false);

        $this->actingAs($user)
            ->get(route('admin.broadcast-recordings.audio', $recording))
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/ogg');
    }

    public function test_recording_service_starts_stops_and_finalizes_one_recording_per_session(): void
    {
        Storage::fake('broadcast_recordings');

        $liveKit = Mockery::mock(LiveKitService::class);
        $liveKit->shouldReceive('ensureRoom')->once()->with('recording-room')->andReturn(true);
        $liveKit->shouldReceive('startRoomRecording')
            ->once()
            ->withArgs(fn (string $room, string $path): bool => $room === 'recording-room' && str_ends_with($path, '.ogg'))
            ->andReturn(['egress_id' => 'EG_lifecycle', 'status' => 1]);
        $liveKit->shouldReceive('stopRoomRecording')->once()->with('EG_lifecycle')->andReturn(true);

        $service = new BroadcastRecordingService($liveKit);
        $session = BroadcastSession::query()->create([
            'broadcast_channel_id' => $this->channel('recording-room')->id,
            'broadcaster_id' => $this->staffUser()->id,
            'room_name' => 'recording-room',
            'status' => 'live',
            'started_at' => now(),
        ]);

        $recording = $service->start($session);
        $this->assertSame('active', $recording->status);
        $this->assertSame('EG_lifecycle', $recording->egress_id);
        $this->assertSame($recording->id, $service->start($session)->id);
        $this->assertTrue($service->stop($session->refresh()->load('recording')));
        $this->assertSame('finalizing', $recording->refresh()->status);

        Storage::disk('broadcast_recordings')->put($recording->file_path, str_repeat('x', 4096));
        $service->handleWebhook('egress_ended', [
            'egressId' => 'EG_lifecycle',
            'status' => 3,
            'endedAt' => now()->timestamp * 1_000_000_000,
            'fileResults' => [[
                'duration' => 125 * 1_000_000_000,
                'size' => 4096,
            ]],
        ]);

        $recording->refresh();
        $this->assertSame('complete', $recording->status);
        $this->assertSame(125, $recording->duration_seconds);
        $this->assertSame(4096, $recording->file_size);
        $this->assertTrue($recording->isPlayable());
    }

    private function staffUser(): User
    {
        $user = User::factory()->create([
            'user_type' => 'staff',
            'status' => 'active',
        ]);
        $user->givePermissionTo(Permission::findOrCreate('broadcasts.view', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('broadcasts.broadcast', 'web'));

        return $user;
    }

    private function channel(string $room = 'studio-recording-room'): BroadcastChannel
    {
        return BroadcastChannel::query()->create([
            'name' => 'Recorded Channel',
            'slug' => 'recorded-channel-'.strtolower(substr(md5($room), 0, 8)),
            'room_name' => $room,
            'is_active' => true,
        ]);
    }
}
