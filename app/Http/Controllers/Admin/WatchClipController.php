<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WatchClip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class WatchClipController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('watch.manage');

        $clips = WatchClip::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($s) => $s
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('creator_name', 'like', '%'.$request->string('q').'%')))
            ->orderBy('position')
            ->orderByDesc('published_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.watch-clips.index', compact('clips'));
    }

    public function create(): View
    {
        $this->authorize('watch.manage');

        return view('admin.watch-clips.form', ['clip' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('watch.manage');

        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']).'-'.Str::random(5);
        $data['video_path'] = $this->uploadFile($request, 'video', 'watch/clips/videos', null);
        $data['thumbnail_path'] = $this->uploadFile($request, 'thumbnail', 'watch/clips/thumbnails', null);
        $data['creator_avatar_path'] = $this->uploadFile($request, 'creator_avatar', 'watch/clips/avatars', null);
        $data['is_published'] = $request->boolean('is_published');

        WatchClip::query()->create($data);

        return redirect()->route('admin.watch-clips.index')
            ->with('success', 'Clip created successfully.');
    }

    public function edit(WatchClip $watchClip): View
    {
        $this->authorize('watch.manage');

        return view('admin.watch-clips.form', ['clip' => $watchClip]);
    }

    public function update(Request $request, WatchClip $watchClip): RedirectResponse
    {
        $this->authorize('watch.manage');

        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: $watchClip->slug;

        if ($request->hasFile('video')) {
            $this->deleteFile($watchClip->video_path);
            $data['video_path'] = $this->uploadFile($request, 'video', 'watch/clips/videos', null);
        } elseif ($request->boolean('remove_video')) {
            $this->deleteFile($watchClip->video_path);
            $data['video_path'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            $this->deleteFile($watchClip->thumbnail_path);
            $data['thumbnail_path'] = $this->uploadFile($request, 'thumbnail', 'watch/clips/thumbnails', null);
        } elseif ($request->boolean('remove_thumbnail')) {
            $this->deleteFile($watchClip->thumbnail_path);
            $data['thumbnail_path'] = null;
        }

        if ($request->hasFile('creator_avatar')) {
            $this->deleteFile($watchClip->creator_avatar_path);
            $data['creator_avatar_path'] = $this->uploadFile($request, 'creator_avatar', 'watch/clips/avatars', null);
        }

        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published'] && ! $watchClip->published_at ? now() : $watchClip->published_at;

        $watchClip->update($data);

        return redirect()->route('admin.watch-clips.edit', $watchClip)
            ->with('success', 'Clip updated successfully.');
    }

    public function destroy(WatchClip $watchClip): RedirectResponse
    {
        $this->authorize('watch.manage');

        $this->deleteFile($watchClip->video_path);
        $this->deleteFile($watchClip->thumbnail_path);
        $this->deleteFile($watchClip->creator_avatar_path);
        $watchClip->delete();

        return redirect()->route('admin.watch-clips.index')
            ->with('success', 'Clip deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'title_bn'        => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'description_bn'  => ['nullable', 'string', 'max:2000'],
            'slug'            => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9\-]*$/'],
            'creator_name'    => ['nullable', 'string', 'max:120'],
            'creator_handle'  => ['nullable', 'string', 'max:80'],
            'audio_track'     => ['nullable', 'string', 'max:255'],
            'hashtags'        => ['nullable', 'string', 'max:500'],
            'position'        => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function uploadFile(Request $request, string $field, string $directory, ?string $currentPath): ?string
    {
        if ($request->hasFile($field)) {
            return $request->file($field)->store($directory, 'public') ?: $currentPath;
        }

        return $currentPath;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
