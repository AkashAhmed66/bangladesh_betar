<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Stores public catalogue artwork and safely replaces/removes old files.
 */
class ArtworkService
{
    /** @return array<int, string> */
    public static function rules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'];
    }

    public function sync(
        Request $request,
        string $directory,
        ?string $currentPath,
        string $fileField = 'artwork',
        string $removeField = 'remove_artwork',
    ): ?string {
        if ($request->hasFile($fileField)) {
            $newPath = $request->file($fileField)->store($directory, 'public');

            if (! is_string($newPath) || $newPath === '') {
                throw new RuntimeException('The image could not be stored. Please try again.');
            }

            $this->delete($currentPath);

            return $newPath;
        }

        if ($request->boolean($removeField)) {
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
