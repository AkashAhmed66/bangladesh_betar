<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\NewsArticle;
use App\Models\User;
use App\Models\WatchEpisode;
use App\Models\WatchShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class EditorialApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_requires_assigned_approval_before_an_authorised_user_can_publish(): void
    {
        [$creator, $approver] = $this->editorialUsers('news');
        $wrongRole = $this->staffWithPermissions(['approvals.view', 'approvals.act', 'records.view-all']);
        $article = NewsArticle::factory()->create([
            'created_by' => $creator->id,
            'slug' => 'approval-required-news',
            'is_published' => false,
            'approval_status' => 'draft',
            'published_at' => null,
        ]);

        $this->actingAs($creator)->post(route('admin.news-articles.submit', $article))
            ->assertRedirect()
            ->assertSessionHas('success');

        $article->refresh();
        $this->assertSame('pending', $article->approval_status);
        $this->assertFalse($article->is_published);
        $approval = Approval::query()->whereMorphedTo('approvable', $article)->firstOrFail();

        $this->actingAs($wrongRole)->post(route('admin.approvals.act', $approval), ['action' => 'approve'])
            ->assertForbidden();

        $this->actingAs($approver)->post(route('admin.news-articles.publish', $article))
            ->assertSessionHas('error');
        $this->assertFalse($article->fresh()->is_published);

        $this->actingAs($approver)->post(route('admin.approvals.act', $approval), ['action' => 'approve'])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('approved', $article->fresh()->approval_status);

        $this->actingAs($approver)->post(route('admin.news-articles.publish', $article))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertTrue($article->fresh()->is_published);
        $this->getJson(route('api.v1.news.show', $article->slug))->assertOk();

        $this->actingAs($creator)->put(route('admin.news-articles.update', $article), [
            'title' => 'Edited after approval',
            'slug' => $article->slug,
            'summary' => $article->summary,
            'category' => $article->category,
            'body_text' => implode("\n\n", $article->body),
            'read_time_minutes' => $article->read_time_minutes,
            'position' => $article->position,
            'is_featured' => (int) $article->is_featured,
        ])->assertRedirect(route('admin.news-articles.edit', $article));

        $article->refresh();
        $this->assertSame('draft', $article->approval_status);
        $this->assertFalse($article->is_published);
        $this->getJson(route('api.v1.news.show', $article->slug))->assertNotFound();
    }

    public function test_watch_show_and_episode_changes_require_fresh_approval(): void
    {
        [$creator, $approver] = $this->editorialUsers('watch');
        $show = WatchShow::factory()->create([
            'created_by' => $creator->id,
            'slug' => 'approval-required-watch',
            'is_published' => false,
            'approval_status' => 'draft',
            'published_at' => null,
        ]);
        $episode = WatchEpisode::factory()->for($show, 'show')->create([
            'video_path' => 'watch/videos/test.mp4',
            'is_published' => true,
        ]);

        $this->actingAs($creator)->post(route('admin.watch-shows.submit', $show))->assertSessionHas('success');
        $approval = Approval::query()->whereMorphedTo('approvable', $show)->firstOrFail();
        $this->actingAs($approver)->post(route('admin.approvals.act', $approval), ['action' => 'approve'])->assertSessionHas('success');
        $this->actingAs($approver)->post(route('admin.watch-shows.publish', $show))->assertSessionHas('success');
        $this->assertTrue($show->fresh()->is_published);

        $this->actingAs($creator)->put(route('admin.watch-episodes.update', $episode), [
            'title' => 'Revised episode',
            'description' => $episode->description,
            'duration_minutes' => $episode->duration_minutes,
            'position' => $episode->position,
            'is_published' => 1,
        ])->assertRedirect(route('admin.watch-shows.edit', $show));

        $show->refresh();
        $this->assertSame('draft', $show->approval_status);
        $this->assertFalse($show->is_published);
        $this->getJson(route('api.v1.watch.preview', $show->slug))->assertNotFound();
    }

    /** @return array{User, User} */
    private function editorialUsers(string $module): array
    {
        $creator = $this->staffWithPermissions(["{$module}.view", "{$module}.manage", 'approvals.view']);
        $approver = $this->staffWithPermissions(["{$module}.view", "{$module}.publish", 'approvals.view', 'approvals.act', 'records.view-all']);
        $role = Role::findOrCreate('Approver', 'web');
        $approver->assignRole($role);

        return [$creator, $approver];
    }

    /** @param list<string> $permissions */
    private function staffWithPermissions(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->givePermissionTo($permissions);

        return $user;
    }
}
