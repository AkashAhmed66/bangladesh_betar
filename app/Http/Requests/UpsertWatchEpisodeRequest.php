<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\VideoUploadService;
use Illuminate\Foundation\Http\FormRequest;

final class UpsertWatchEpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('watch.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_bn' => ['nullable', 'string', 'max:5000'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'summary_bn' => ['nullable', 'string', 'max:5000'],
            'audio_languages' => ['nullable', 'string', 'max:500'],
            'subtitle_languages' => ['nullable', 'string', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'position' => ['required', 'integer', 'min:1', 'max:65535'],
            'is_published' => ['required', 'boolean'],
            'video' => VideoUploadService::rules(),
            'remove_video' => ['boolean'],
        ];
    }
}
