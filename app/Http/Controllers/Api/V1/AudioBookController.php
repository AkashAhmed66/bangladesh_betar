<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AudioBookResource;
use App\Models\AudioBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * M31 — public Audio Books: published, PREMIUM-only listening with
 * read-along text. The list is browsable by everyone (locked cards);
 * the content (text + signed stream URLs) requires a premium account.
 */
class AudioBookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $books = AudioBook::query()->published()->with('user')
            ->latest('published_at')
            ->paginate(24);

        return AudioBookResource::collection($books)->response();
    }

    public function show(Request $request, AudioBook $audioBook): JsonResponse
    {
        abort_unless($audioBook->status === 'published', 404);

        $user = $request->user();
        abort_unless($user !== null, 401, 'Sign in with a Premium account to read and listen to audio books.');
        abort_unless($user->isPremium(), 403, 'Audio books are a Premium feature — upgrade to read and listen.');

        $audioBook->with_content = true;
        $audioBook->load('user');

        return response()->json(['data' => (new AudioBookResource($audioBook))->resolve()]);
    }

    /** Signed, expiring stream — issued only to premium accounts via show(). */
    public function play(Request $request, AudioBook $audioBook, string $voice): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Expired or invalid streaming URL.');
        abort_unless($audioBook->status === 'published', 404);
        abort_unless(in_array($voice, ['male', 'female', 'enhanced'], true), 404);

        $path = match ($voice) {
            'male' => $audioBook->audio_male_path,
            'female' => $audioBook->audio_female_path,
            'enhanced' => $audioBook->audio_enhanced_path,
        };
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404, 'Audio not available.');

        return response()->file($disk->path($path), [
            'Content-Type' => str_ends_with($path, '.mp3') ? 'audio/mpeg' : 'audio/wav',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store',
        ]);
    }
}
