<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\BroadcastSession;
use App\Models\User;
use App\Services\LiveKitService;
use App\Services\SpeakRequestStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BroadcastChannelStopTest extends TestCase
{
    use RefreshDatabase;

    public function test_stopping_a_broadcast_ends_the_session_and_terminates_the_livekit_room(): void
    {
        $liveKit = Mockery::mock(LiveKitService::class);
        $liveKit->shouldReceive('terminateRoom')
            ->once()
            ->with('test-live-room')
            ->andReturn(true);
        $this->app->instance(LiveKitService::class, $liveKit);

        $user = User::factory()->create([
            'user_type' => 'staff',
            'status' => 'active',
        ]);
        $user->givePermissionTo(Permission::findOrCreate('broadcasts.broadcast', 'web'));

        $channel = BroadcastChannel::query()->create([
            'name' => 'Test Live Channel',
            'slug' => 'test-live-channel',
            'room_name' => 'test-live-room',
            'is_active' => true,
        ]);
        $session = BroadcastSession::query()->create([
            'broadcast_channel_id' => $channel->id,
            'broadcaster_id' => $user->id,
            'room_name' => $channel->room_name,
            'status' => 'live',
            'current_listeners' => 4,
            'started_at' => now()->subMinutes(10),
        ]);
        SpeakRequestStore::add($channel->room_name, 'listener-1', 'Listener');

        $this->actingAs($user)
            ->post(route('admin.broadcast-channels.stop', $channel))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'session_ended' => true,
                'room_terminated' => true,
            ]);

        $this->assertDatabaseHas('broadcast_sessions', [
            'id' => $session->id,
            'status' => 'ended',
            'current_listeners' => 0,
        ]);
        $this->assertNotNull($session->fresh()->ended_at);
        $this->assertSame([], SpeakRequestStore::all($channel->room_name));
    }
}
