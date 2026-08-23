<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\YouTube;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class NewsArticleMediaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->media_type,
            'url' => $this->media_type === 'youtube' ? $this->path : Storage::disk($this->disk)->url($this->path),
            'embed_url' => $this->media_type === 'youtube' ? YouTube::embedUrl($this->path) : null,
            'name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'position' => $this->position,
        ];
    }
}
