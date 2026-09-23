<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\BroadcastSession;
use App\Models\Plan;
use App\Models\Station;
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
        $station = $this->station();

        $this->actingAs($this->staff(['watch.view', 'watch.manage']))
            ->post(route('admin.watch-live-channels.store'), [
                'name' => 'Parliament Live',
                'name_bn' => 'সংসদ সরাসরি',
                'description' => 'National parliamentary coverage.',
                'station_id' => $station->id,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.watch-live-channels.index'));

        $this->assertDatabaseHas('broadcast_channels', [
            'name' => 'Parliament Live',
            'channel_type' => 'video',
            'is_active' => true,
        ]);
    }

    public function test_watch_channel_station_is_required_and_must_exist(): void
    {
        $user = $this->staff(['watch.view', 'watch.manage']);

        $this->actingAs($user)
            ->from(route('admin.watch-live-channels.create'))
            ->post(route('admin.watch-live-channels.store'), ['name' => 'Missing station'])
            ->assertRedirect(route('admin.watch-live-channels.create'))
            ->assertSessionHasErrors('station_id');

        $this->actingAs($user)
            ->from(route('admin.watch-live-channels.create'))
            ->post(route('admin.watch-live-channels.store'), [
                'name' => 'Unknown station',
                'station_id' => 999999,
            ])
            ->assertRedirect(route('admin.watch-live-channels.create'))
            ->assertSessionHasErrors('station_id');
    }

    public function test_watch_channel_exposes_station_metadata(): void
    {
        $station = $this->station();
        $channel = $this->channel('video', 'station-watch-room');
        $channel->update(['station_id' => $station->id]);

        $this->getJson(route('api.v1.watch-live-channels.show', $channel))
            ->assertOk()
            ->assertJsonPath('data.station_id', $station->id)
            ->assertJsonPath('data.station', $station->name)
            ->assertJsonPath('data.station_bn', $station->name_bn);
    }

    public function test_audio_channel_station_is_required(): void
    {
        $user = $this->staff(['broadcasts.view', 'broadcasts.manage']);

        $this->actingAs($user)
            ->from(route('admin.broadcast-channels.create'))
            ->post(route('admin.broadcast-channels.store'), ['name' => 'Missing station'])
            ->assertRedirect(route('admin.broadcast-channels.create'))
            ->assertSessionHasErrors('station_id');
    }

    public function test_audio_channel_exposes_station_metadata(): void
    {
        $station = $this->station();
        $channel = $this->channel('audio', 'station-audio-room');
        $channel->update(['station_id' => $station->id]);

        $this->getJson(route('api.v1.live-channels.show', $channel))
            ->assertOk()
            ->assertJsonPath('data.station_id', $station->id)
            ->assertJsonPath('data.station', $station->name)
            ->assertJsonPath('data.station_bn', $station->name_bn);
    }

    public function test_live_radio_catalogue_contains_every_active_audio_channel_and_marks_live_state(): void
    {
        $station = $this->station();
        $live = $this->channel('audio', 'catalogue-live-room');
        $live->update([
            'name' => 'National Service',
            'name_bn' => 'জাতীয় সেবা',
            'station_id' => $station->id,
        ]);
        BroadcastSession::query()->create([
            'broadcast_channel_id' => $live->id,
            'room_name' => $live->room_name,
            'status' => 'live',
            'started_at' => now()->subMinute(),
            'current_listeners' => 8,
            'peak_listeners' => 8,
        ]);

        $offAir = $this->channel('audio', 'catalogue-offair-room');
        $offAir->update([
            'name' => 'Regional Service',
            'name_bn' => 'আঞ্চলিক সেবা',
            'station_id' => $station->id,
        ]);

        $inactive = $this->channel('audio', 'catalogue-inactive-room');
        $inactive->update(['is_active' => false]);
        $this->channel('video', 'catalogue-video-room');

        $response = $this->getJson(route('api.v1.live-channels.index'))->assertOk();
        $catalogue = collect($response->json('data'));
        $liveData = $catalogue->firstWhere('id', $live->id);
        $offAirData = $catalogue->firstWhere('id', $offAir->id);

        $this->assertNotNull($liveData);
        $this->assertTrue($liveData['is_live']);
        $this->assertSame(8, $liveData['listener_count']);
        $this->assertSame($station->id, $liveData['station_id']);
        $this->assertSame($station->name, $liveData['station']);
        $this->assertSame($station->name_bn, $liveData['station_bn']);
        $this->assertNotNull($offAirData);
        $this->assertFalse($offAirData['is_live']);
        $this->assertSame(0, $offAirData['listener_count']);
        $this->assertFalse($catalogue->contains('id', $inactive->id));
    }

    public function test_audio_listener_token_is_available_only_for_an_active_live_channel(): void
    {
        $liveKit = Mockery::mock(LiveKitService::class);
        $liveKit->shouldReceive('isConfigured')->once()->andReturnTrue();
        $liveKit->shouldReceive('listenerToken')->once()->andReturn([
            'ws_url' => 'ws://livekit.test',
            'token' => 'audio-listener-token',
            'room' => 'token-live-room',
        ]);
        $this->app->instance(LiveKitService::class, $liveKit);

        $offAir = $this->channel('audio', 'token-offair-room');
        $this->postJson(route('api.v1.live-channels.token', $offAir))
            ->assertNotFound();

        $inactive = $this->channel('audio', 'token-inactive-room');
        $inactive->update(['is_active' => false]);
        BroadcastSession::query()->create([
            'broadcast_channel_id' => $inactive->id,
            'room_name' => $inactive->room_name,
            'status' => 'live',
            'started_at' => now(),
        ]);
        $this->postJson(route('api.v1.live-channels.token', $inactive))
            ->assertNotFound();

        $live = $this->channel('audio', 'token-live-room');
        BroadcastSession::query()->create([
            'broadcast_channel_id' => $live->id,
            'room_name' => $live->room_name,
            'status' => 'live',
            'started_at' => now(),
        ]);

        $this->postJson(route('api.v1.live-channels.token', $live))
            ->assertOk()
            ->assertJsonPath('token', 'audio-listener-token')
            ->assertJsonPath('room', $live->room_name);
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

        $watchCatalogue = collect($this->getJson(route('api.v1.watch-live-channels.index'))
            ->assertOk()
            ->json('data'));
        $videoData = $watchCatalogue->firstWhere('id', $video->id);
        $this->assertNotNull($videoData);
        $this->assertSame('watch_live_channel', $videoData['type']);
        $this->assertTrue($videoData['is_live']);
        $this->assertSame(12, $videoData['viewer_count']);

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

    private function station(): Station
    {
        return Station::query()->create([
            'name' => 'Dhaka Betar',
            'name_bn' => 'ঢাকা বেতার',
            'code' => 'DHK-'.uniqid(),
        ]);
    }
}
