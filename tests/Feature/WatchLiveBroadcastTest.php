<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\BroadcastSession;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\LiveKitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class WatchLiveBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_watch_manager_can_create_a_video_channel(): void
    {
        $this->actingAs($this->staff(['watch.view', 'watch.manage']))
            ->post(route('admin.watch-live-channels.store'), [
                'name' => 'Parliament Live',
                'name_bn' => 'সংসদ সরাসরি',
                'description' => 'National parliamentary coverage.',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.watch-live-channels.index'));

        $this->assertDatabaseHas('broadcast_channels', [
            'name' => 'Parliament Live',
            'channel_type' => 'video',
            'is_active' => true,
        ]);
    }

    public function test_broadcaster_can_start_and_completely_stop_watch_live_video(): void
    {
        $liveKit = Mockery::mock(LiveKitService::class);
        $liveKit->shouldReceive('isConfigured')->once()->andReturnTrue();
        $liveKit->shouldReceive('publisherToken')->once()->andReturn([
            'ws_url' => 'ws://livekit.test',
            'token' => 'publisher-token',
            'room' => 'watch-room',
        ]);
        $liveKit->shouldReceive('terminateRoom')->once()->with('watch-room')->andReturnTrue();
        $this->app->instance(LiveKitService::class, $liveKit);

        $user = $this->staff(['watch.view', 'watch.broadcast']);
        $channel = $this->channel('video', 'watch-room');

        $this->actingAs($user)
            ->postJson(route('admin.watch-live-channels.go-live', $channel), ['title' => 'Evening bulletin'])
            ->assertOk()
            ->assertJsonPath('token', 'publisher-token');

        $session = BroadcastSession::query()->firstOrFail();
        $this->assertSame('live', $session->status);
        $this->assertSame('Evening bulletin', $session->title);
        $this->assertSame($user->id, $session->broadcaster_id);

        $this->actingAs($user)
            ->postJson(route('admin.watch-live-channels.stop', $channel))
            ->assertOk()
            ->assertJsonPath('room_terminated', true);

        $this->assertDatabaseHas('broadcast_sessions', [
            'id' => $session->id,
            'status' => 'ended',
            'current_listeners' => 0,
        ]);
        $this->assertNotNull($session->fresh()->ended_at);
    }

    public function test_watch_live_discovery_is_public_but_viewing_requires_premium(): void
    {
        $video = $this->channel('video', 'public-watch-room');
        $audio = $this->channel('audio', 'public-audio-room');
        BroadcastSession::query()->create([
            'broadcast_channel_id' => $video->id,
            'room_name' => $video->room_name,
            'status' => 'live',
            'started_at' => now(),
            'current_listeners' => 12,
            'peak_listeners' => 15,
        ]);

        $this->getJson(route('api.v1.watch-live-channels.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $video->id)
            ->assertJsonPath('data.0.type', 'watch_live_channel')
            ->assertJsonPath('data.0.is_live', true)
            ->assertJsonPath('data.0.viewer_count', 12);

        $this->getJson(route('api.v1.live-channels.show', $video))->assertNotFound();
        $this->getJson(route('api.v1.watch-live-channels.show', $audio))->assertNotFound();

        $this->postJson(route('api.v1.watch-live-channels.token', $video))->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['user_type' => 'listener', 'status' => 'active']));
        $this->postJson(route('api.v1.watch-live-channels.token', $video))->assertForbidden();

        $liveKit = Mockery::mock(LiveKitService::class);
        $liveKit->shouldReceive('isConfigured')->once()->andReturnTrue();
        $liveKit->shouldReceive('listenerToken')->once()->andReturn([
            'ws_url' => 'ws://livekit.test',
            'token' => 'viewer-token',
            'room' => $video->room_name,
        ]);
        $this->app->instance(LiveKitService::class, $liveKit);

        Sanctum::actingAs($this->premiumUser());
        $this->postJson(route('api.v1.watch-live-channels.token', $video))
            ->assertOk()
            ->assertJsonPath('token', 'viewer-token');
    }

    /** @param list<string> $permissions */
    private function staff(array $permissions): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function channel(string $type, string $room): BroadcastChannel
    {
        return BroadcastChannel::query()->create([
            'name' => ucfirst($type).' channel',
            'slug' => $type.'-'.str_replace('-room', '', $room),
            'room_name' => $room,
            'channel_type' => $type,
            'is_active' => true,
        ]);
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create(['user_type' => 'listener', 'status' => 'active']);
        $plan = Plan::query()->create([
            'name' => 'Premium',
            'code' => 'premium',
            'features' => ['premium_content' => 'full'],
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        return $user;
    }
}
