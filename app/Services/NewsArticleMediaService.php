<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NewsArticle;
use App\Models\NewsArticleMedia;
use App\Support\YouTube;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class NewsArticleMediaService
{
    /** @var array<string, string> */
    private const UPLOAD_FIELDS = [
        'images' => 'image',
        'videos' => 'video',
        'audios' => 'audio',
        'documents' => 'document',
    ];

    public function sync(NewsArticle $article, Request $request): void
    {
        $createdMedia = [];

        try {
            foreach (self::UPLOAD_FIELDS as $field => $mediaType) {
                $createdMedia = array_merge(
                    $createdMedia,
                    $this->storeUploadedFiles($article, $request->file($field, []), $mediaType),
                );
            }
            $createdMedia = array_merge(
                $createdMedia,
                $this->storeYouTubeLinks($article, $request->input('youtube_links', [])),
            );
        } catch (Throwable $exception) {
            collect($createdMedia)->each(function (NewsArticleMedia $media): void {
                $this->deleteMedia($media);
            });

            throw $exception;
        }

        $this->removeSelected($article, $request->input('remove_media', []));
    }

    public function deleteAll(NewsArticle $article): void
    {
        $article->media()->get()->each(function (NewsArticleMedia $media): void {
            $this->deleteMedia($media);
        });
    }

    private function removeSelected(NewsArticle $article, mixed $selectedIds): void
    {
        $ids = collect(is_array($selectedIds) ? $selectedIds : [])
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $article->media()->whereKey($ids)->get()->each(function (NewsArticleMedia $media): void {
            $this->deleteMedia($media);
        });
    }

    /** @return array<int, NewsArticleMedia> */
    private function storeYouTubeLinks(NewsArticle $article, mixed $links): array
    {
        $canonicalUrls = collect(is_array($links) ? $links : [])
            ->filter(fn (mixed $url): bool => is_string($url) && filled($url))
            ->map(fn (string $url): ?string => YouTube::canonicalUrl($url))
            ->filter()
            ->unique()
            ->values();

        if ($canonicalUrls->isEmpty()) {
            return [];
        }

        $existing = $article->media()->where('media_type', 'youtube')->pluck('path');
        $nextPosition = (int) $article->media()->max('position') + 1;
        $createdMedia = [];

        foreach ($canonicalUrls as $url) {
            if ($existing->contains($url)) {
                continue;
            }

            $videoId = YouTube::videoId($url);

            $createdMedia[] = $article->media()->create([
                'media_type' => 'youtube',
                'disk' => 'external',
                'path' => $url,
                'original_name' => "YouTube · {$videoId}",
                'mime_type' => 'text/html',
                'size_bytes' => 0,
                'position' => $nextPosition++,
            ]);
        }

        return $createdMedia;
    }

    private function deleteMedia(NewsArticleMedia $media): void
    {
        if ($media->disk !== 'external') {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();
    }

    /** @param UploadedFile|array<int, UploadedFile>|null $files */
    /** @return array<int, NewsArticleMedia> */
    private function storeUploadedFiles(NewsArticle $article, UploadedFile|array|null $files, string $mediaType): array
    {
        $uploads = $files instanceof UploadedFile ? [$files] : ($files ?? []);
        $storedPaths = [];
        $createdMedia = [];
        $nextPosition = (int) $article->media()->max('position') + 1;

        try {
            foreach ($uploads as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $path = $file->store("portal/news/{$article->id}/{$mediaType}", 'public');
                if (! is_string($path) || $path === '') {
                    throw new RuntimeException("The {$mediaType} file could not be stored.");
                }

                $storedPaths[] = $path;
                $createdMedia[] = $article->media()->create([
                    'media_type' => $mediaType,
                    'disk' => 'public',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'position' => $nextPosition++,
                ]);
            }
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            collect($createdMedia)->each(fn (NewsArticleMedia $media) => $media->delete());
            throw $exception;
        }

        return $createdMedia;
    }
}
