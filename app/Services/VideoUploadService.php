<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class VideoUploadService
{
    /** @return array<int, string> */
    public static function rules(): array
    {
        return ['nullable', 'file', 'mimetypes:video/mp4,video/webm', 'max:524288'];
    }

    public function sync(Request $request, ?string $currentPath): ?string
    {
        if ($request->hasFile('video')) {
            $newPath = $request->file('video')->store('watch/videos', 'public');

            if (! is_string($newPath) || $newPath === '') {
                throw new RuntimeException('The video could not be stored. Please try again.');
            }

            $this->delete($currentPath);

            return $newPath;
        }

        if ($request->boolean('remove_video')) {
            $this->delete($currentPath);

            return null;
        }

        return $currentPath;
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
