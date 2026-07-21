<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'rating' => $this->rating,
            'status' => $this->status,
            'author' => $this->whenLoaded('user', fn () => $this->user?->name),
            'user_id' => $this->user_id,
            'is_mine' => $this->when($request->user() !== null, fn () => $request->user()?->id === $this->user_id),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
