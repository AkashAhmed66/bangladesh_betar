<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContentReaction;
use App\Services\ContentReactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ContentReactionController extends Controller
{
    public function __construct(private readonly ContentReactionService $reactions) {}

    public function show(Request $request, string $type, int $id): JsonResponse
    {
        $content = $this->reactions->findPublicContent($type, $id);

        return response()->json(['data' => $this->reactions->summary($content, $request->user())]);
    }

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $data = $request->validate([
            'reaction' => ['required', 'string', Rule::in([ContentReaction::LIKE, ContentReaction::DISLIKE])],
        ]);
        $content = $this->reactions->findPublicContent($type, $id);

        return response()->json([
            'data' => $this->reactions->toggle($content, $request->user(), $data['reaction']),
        ]);
    }
}
