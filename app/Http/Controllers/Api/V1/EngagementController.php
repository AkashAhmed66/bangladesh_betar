<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\AudioAsset;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Feedback;
use App\Models\IssueReport;
use App\Models\Rating;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * M26 — listener engagement: comments, ratings, reports, issue reports
 * and general feedback, with moderation applied (FR-ENG-*).
 */
class EngagementController extends Controller
{
    /** Public: approved comments on an asset (FR-ENG-01). */
    public function comments(AudioAsset $asset): JsonResponse
    {
        abort_unless($asset->isPublished(), 404);

        $comments = $asset->comments()->approved()->with('user')->latest()->paginate(20);

        return CommentResource::collection($comments)->response();
    }

    public function postComment(Request $request, AudioAsset $asset): JsonResponse
    {
        abort_unless($asset->isPublished(), 404);
        abort_unless($asset->allow_comments, 403, 'Comments are disabled for this content.');

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        // Pre/post moderation per configured policy (FR-ENG-03).
        $mode = Setting::get('moderation_mode', 'post');
        $status = $mode === 'pre' ? 'pending' : 'approved';

        if (Setting::get('profanity_filter_enabled', true) && $this->containsProfanity($data['body'])) {
            $status = 'pending';
        }

        $comment = $asset->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
            'status' => $status,
        ]);
        $comment->load('user');

        return response()->json([
            'message' => $status === 'approved' ? 'Comment posted.' : 'Comment submitted for moderation.',
            'data' => (new CommentResource($comment))->resolve(),
        ], 201);
    }

    public function deleteComment(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    /** FR-ENG-02 — one rating per user per item, changeable. */
    public function rate(Request $request, AudioAsset $asset): JsonResponse
    {
        abort_unless($asset->isPublished(), 404);
        $data = $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5']]);

        Rating::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'ratable_type' => 'audio_asset', 'ratable_id' => $asset->id],
            ['rating' => $data['rating']],
        );

        // Recompute aggregate.
        $agg = Rating::query()->where('ratable_type', 'audio_asset')->where('ratable_id', $asset->id)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as rating_count')->first();
        $asset->update([
            'avg_rating' => round((float) $agg->avg_rating, 2),
            'rating_count' => (int) $agg->rating_count,
        ]);

        return response()->json([
            'message' => 'Rating saved.',
            'avg_rating' => $asset->avg_rating,
            'rating_count' => $asset->rating_count,
        ]);
    }

    /** FR-ENG-04 — report a comment or content item. */
    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reportable_type' => ['required', Rule::in(['comment', 'audio_asset'])],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(['abuse', 'spam', 'copyright', 'inappropriate', 'other'])],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        ContentReport::query()->create($data + [
            'reporter_id' => $request->user()?->id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Report submitted. Thank you.'], 201);
    }

    /** FR-ENG-07 — report an issue on a playable item. */
    public function reportIssue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audio_asset_id' => ['nullable', 'exists:audio_assets,id'],
            'issue_type' => ['required', Rule::in(['broken_audio', 'wrong_metadata', 'inappropriate', 'other'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        IssueReport::query()->create($data + [
            'user_id' => $request->user()?->id,
            'status' => 'open',
        ]);

        return response()->json(['message' => 'Issue reported. Our team will review it.'], 201);
    }

    /** FR-ENG-09 — general feedback channel. */
    public function feedback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['general', 'suggestion', 'complaint', 'technical'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Feedback::query()->create($data + [
            'user_id' => $request->user()?->id,
            'status' => 'new',
        ]);

        return response()->json(['message' => 'Thank you for your feedback.'], 201);
    }

    private function containsProfanity(string $text): bool
    {
        $blocklist = ['badword1', 'badword2']; // placeholder EN/BN profanity list
        $lower = mb_strtolower($text);
        foreach ($blocklist as $word) {
            if (str_contains($lower, $word)) {
                return true;
            }
        }

        return false;
    }
}
