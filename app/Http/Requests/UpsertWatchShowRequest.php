<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\WatchShow;
use App\Services\ArtworkService;
use App\Services\VideoUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertWatchShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('watch.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $show = $this->route('watch_show');

        return [
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('watch_shows', 'slug')->ignore($show instanceof WatchShow ? $show->id : null)],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'eyebrow_bn' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'description_bn' => ['nullable', 'string', 'max:5000'],
            'watch_category_id' => ['nullable', 'required_without:category', 'integer', Rule::exists('watch_categories', 'id')->where('is_active', true)],
            'category' => ['nullable', 'required_without:watch_category_id', 'string', Rule::exists('watch_categories', 'name')],
            'genres' => ['nullable', 'string', 'max:1000'],
            'creators' => ['nullable', 'string', 'max:2000'],
            'cast' => ['nullable', 'string', 'max:5000'],
            'audio_languages' => ['nullable', 'string', 'max:500'],
            'subtitle_languages' => ['nullable', 'string', 'max:500'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 2)],
            'rating' => ['nullable', 'string', 'max:20'],
            'age_restriction' => ['nullable', 'string', 'max:20'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_featured' => ['required', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'artwork' => ArtworkService::rules(),
            'remove_artwork' => ['boolean'],
            'trailer' => VideoUploadService::rules(),
            'remove_trailer' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => str($this->string('title'))->slug()->toString()]);
        }
    }
}
