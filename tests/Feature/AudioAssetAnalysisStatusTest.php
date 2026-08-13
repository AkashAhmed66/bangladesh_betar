<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AiAnalysisJob;
use App\Models\AudioAsset;
use App\Models\Transcript;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AudioAssetAnalysisStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_and_transcript_results_are_available_to_live_page_polling(): void
    {
        $user = $this->staffUser();
        $asset = $this->asset($user);
        $job = AiAnalysisJob::query()->create([
            'audio_asset_id' => $asset->id,
            'status' => 'processing',
            'source_api' => 'analyze',
        ]);

        $this->actingAs($user)
            ->get(route('admin.assets.show', $asset))
            ->assertOk()
            ->assertSee('analysis-status')
            ->assertSee('The results will appear here automatically.');

        $this->actingAs($user)
            ->getJson(route('admin.assets.analysis-status', $asset))
            ->assertOk()
            ->assertJsonPath('pending', true)
            ->assertJsonPath('asset_status', 'analyzing')
            ->assertJsonPath('analysis_status', 'processing');

        $job->update([
            'status' => 'done',
            'summary' => 'No safety issues were detected.',
            'completed_at' => now(),
        ]);
        $asset->update(['status' => 'ai_review']);
        Transcript::query()->create([
            'audio_asset_id' => $asset->id,
            'transcript_type' => 'transcript',
            'full_text' => 'The completed transcript is now visible without refreshing.',
            'is_ai_generated' => true,
            'is_verified' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('admin.assets.analysis-status', $asset))
            ->assertOk()
            ->assertJsonPath('pending', false)
            ->assertJsonPath('asset_status', 'ai_review')
            ->assertJsonPath('analysis_status', 'done');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $html = (string) $response->json('html');
        $this->assertStringContainsString('No safety issues were detected.', $html);
        $this->assertStringContainsString('The completed transcript is now visible without refreshing.', $html);
    }

    public function test_analysis_status_respects_asset_record_visibility(): void
    {
        $owner = $this->staffUser();
        $otherUser = $this->staffUser();
        $asset = $this->asset($owner);

        $this->actingAs($otherUser)
            ->getJson(route('admin.assets.analysis-status', $asset))
            ->assertForbidden();
    }

    private function staffUser(): User
    {
        $user = User::factory()->create([
            'user_type' => 'staff',
            'status' => 'active',
        ]);
        Permission::findOrCreate('assets.view', 'web');
        $user->givePermissionTo('assets.view');

        return $user;
    }

    private function asset(User $owner): AudioAsset
    {
        return AudioAsset::query()->create([
            'archive_no' => 'BB-2026-'.str_pad((string) $owner->id, 6, '0', STR_PAD_LEFT),
            'title' => 'Asynchronous Transcript Test',
            'slug' => 'asynchronous-transcript-test-'.$owner->id,
            'content_type' => 'historical',
            'uploaded_by' => $owner->id,
            'duration_seconds' => 90,
            'status' => 'analyzing',
            'access_level' => 'internal',
            'rights_status' => 'unknown',
        ]);
    }
}
