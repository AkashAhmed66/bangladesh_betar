<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\PackageHlsAudio;
use App\Models\BroadcastChannel;
use App\Models\BroadcastRecording;
use App\Models\BroadcastSession;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BroadcastRecordingService;
use App\Services\LiveKitService;
use App\Support\Hls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BroadcastRecordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('broadcast_recordings');
        Storage::fake('local');
        Cache::flush();
    }

    public function test_studio_paginates_recordings_and_only_offers_protected_playback(): void
    {
        $user = $this->staffUser();
        $channel = $this->channel();

        foreach (range(1, 11) as $number) {
            $recording = $this->completedRecording($channel, $user, $number);
            Storage::disk('local')->put(Hls::dir('broadcast', $recording->id, 'main').'/index.m3u8', '#EXTM3U');
        }

        $firstPage = $this->actingAs($user)
            ->get(route('admin.broadcast-channels.studio', $channel));

        $firstPage->assertOk()
            ->assertSee('Broadcast recordings')
            ->assertSee('Broadcast 11')
            ->assertDontSee('Broadcast 1</p>', false)
            ->assertSee('controlslist="nodownload noplaybackrate"', false)
            ->assertSee('Premium public')
            ->assertSee('Unpublish')
            ->assertSee('recordings=2', false);

        $this->actingAs($user)
            ->get(route('admin.broadcast-channels.studio', [$channel, 'recordings' => 2]))
            ->assertOk()
            ->assertSee('Broadcast 1');

        $this->actingAs($user)
            ->get('/admin/broadcast-recordings/1/download')
            ->assertNotFound();
    }

    public function test_admin_can_unpublish_republish_and_permanently_delete_a_recording(): void
    {
        Queue::fake();
        $user = $this->staffUser();
        $recording = $this->completedRecording($this->channel(), $user, 1);
        $hlsPath = Hls::dir('broadcast', $recording->id, 'main').'/index.m3u8';
        Storage::disk('local')->put($hlsPath, '#EXTM3U');
        $publicUrl = Hls::playlistUrl('broadcast', $recording->id, 'main');

        $this->actingAs($user)
            ->post(route('admin.broadcast-recordings.unpublish', $recording))
            ->assertRedirect();

        $recording->refresh();
        $this->assertFalse($recording->is_published);
        $this->assertNull($recording->published_at);
        Storage::disk('local')->assertExists($hlsPath);
        $this->get($publicUrl)->assertNotFound();
        $this->get(Hls::broadcastRecordingUrl($recording, admin: true))->assertOk();

        $this->actingAs($user)
            ->post(route('admin.broadcast-recordings.publish', $recording))
            ->assertRedirect();

        $recording->refresh();
        $this->assertTrue($recording->isPublished());
        Queue::assertPushed(PackageHlsAudio::class, fn (PackageHlsAudio $job): bool => $job->group === 'broadcast' && $job->id === $recording->id);

        Storage::disk('local')->put($hlsPath, '#EXTM3U');
        $originalPath = $recording->file_path;
        $this->actingAs($user)
            ->delete(route('admin.broadcast-recordings.destroy', $recording))
            ->assertRedirect();

        $this->assertModelMissing($recording);
        Storage::disk('broadcast_recordings')->assertMissing($originalPath);
        Storage::disk('local')->assertMissing($hlsPath);
    }

    public function test_public_archive_is_paginated_and_streaming_is_premium_only(): void
    {
        Queue::fake();
        $channel = $this->channel();
        $broadcaster = $this->staffUser();

        foreach (range(1, 9) as $number) {
            $this->completedRecording($channel, $broadcaster, $number);
        }
        $hidden = $this->completedRecording($channel, $broadcaster, 10);
        $hidden->update(['is_published' => false, 'published_at' => null]);

        $this->getJson('/api/v1/broadcast-recordings')
            ->assertOk()
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('meta.total', 9)
            ->assertJsonPath('data.0.can_play', false)
            ->assertJsonMissing(['title' => 'Broadcast 10']);

        $this->getJson('/api/v1/broadcast-recordings?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $premium = $this->premiumUser();
        Sanctum::actingAs($premium);
        $recording = BroadcastRecording::query()->published()->firstOrFail();
        Storage::disk('local')->put(Hls::dir('broadcast', $recording->id, 'main').'/index.m3u8', '#EXTM3U');

        $this->getJson("/api/v1/broadcast-recordings/{$recording->id}/stream")
            ->assertOk()
            ->assertJsonPath('data.is_hls', true)
            ->assertJsonPath('data.url', fn (string $url): bool => str_contains($url, '/api/v1/hls/broadcast/'));

        Sanctum::actingAs(User::factory()->create(['user_type' => 'listener', 'status' => 'active']));
        $this->getJson("/api/v1/broadcast-recordings/{$recording->id}/stream")
            ->assertForbidden();

        Sanctum::actingAs($premium);
        $this->getJson("/api/v1/broadcast-recordings/{$hidden->id}/stream")
            ->assertNotFound();
    }

    public function test_recording_service_starts_stops_publishes_and_queues_protected_hls(): void
    {
        Queue::fake();
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
        $this->assertTrue($recording->isPublished());
        Queue::assertPushed(PackageHlsAudio::class, fn (PackageHlsAudio $job): bool => $job->group === 'broadcast' && $job->id === $recording->id);
    }

    public function test_published_recordings_are_searchable_by_broadcast_title(): void
    {
        config(['scout.driver' => 'null']);

        $channel = $this->channel();
        $broadcaster = $this->staffUser();
        $recording = $this->completedRecording($channel, $broadcaster, 1);
        $hidden = $this->completedRecording($channel, $broadcaster, 10);
        $hidden->update(['is_published' => false, 'published_at' => null]);

        $this->getJson('/api/v1/search?q=Broadcast%201&type=broadcast_recording')
            ->assertOk()
            ->assertJsonPath('results.broadcast_recordings.data.0.id', $recording->id)
            ->assertJsonMissing(['id' => $hidden->id]);

        $this->getJson('/api/v1/search/suggest?q=Broadcast%201')
            ->assertOk()
            ->assertJsonFragment([
                'text' => 'Broadcast 1',
                'type' => 'broadcast_recording',
            ]);

        $this->assertTrue($recording->shouldBeSearchable());
        $this->assertFalse($hidden->shouldBeSearchable());
        $this->assertSame('Broadcast 1', $recording->toSearchableArray()['title']);
    }

    private function completedRecording(BroadcastChannel $channel, User $user, int $number): BroadcastRecording
    {
        $startedAt = now()->subDays(20 - $number)->setTime(8, $number);
        $session = BroadcastSession::query()->create([
            'broadcast_channel_id' => $channel->id,
            'broadcaster_id' => $user->id,
            'room_name' => $channel->room_name,
            'title' => "Broadcast {$number}",
            'status' => 'ended',
            'peak_listeners' => 10 + $number,
            'started_at' => $startedAt,
            'ended_at' => $startedAt->copy()->addSeconds(120 + $number),
        ]);
        $recording = BroadcastRecording::query()->create([
            'broadcast_session_id' => $session->id,
            'egress_id' => "EG_test_{$number}",
            'status' => 'complete',
            'is_published' => true,
            'published_at' => $startedAt->copy()->addSeconds(120 + $number),
            'disk' => 'broadcast_recordings',
            'file_path' => "channel-{$channel->id}/broadcast-{$number}.ogg",
            'format' => 'ogg',
            'mime_type' => 'audio/ogg',
            'file_size' => 2048 + $number,
            'duration_seconds' => 120 + $number,
            'started_at' => $startedAt,
            'ended_at' => $startedAt->copy()->addSeconds(120 + $number),
        ]);
        Storage::disk('broadcast_recordings')->put($recording->file_path, 'fake-ogg-audio');

        return $recording;
    }

    private function premiumUser(): User
    {
        $plan = Plan::query()->create([
            'name' => 'Premium',
            'code' => 'premium',
            'features' => ['premium_content' => 'full'],
        ]);
        $user = User::factory()->create(['user_type' => 'listener', 'status' => 'active']);
        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        return $user;
    }

    private function staffUser(): User
    {
        $user = User::factory()->create([
            'user_type' => 'staff',
            'status' => 'active',
        ]);
        foreach (['broadcasts.view', 'broadcasts.broadcast', 'broadcasts.manage'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

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
