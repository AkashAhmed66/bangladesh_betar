<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WatchCategory;
use App\Models\WatchShow;
use App\Services\ArtworkService;
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
            'slug' => ['required', 'string', 'max:255', Rule::unique('watch_shows', 'slug')->ignore($show instanceof WatchShow ? $show->id : null)],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', Rule::in(array_column(WatchCategory::cases(), 'value'))],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 2)],
            'rating' => ['nullable', 'string', 'max:20'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_featured' => ['required', 'boolean'],
            'is_published' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'artwork' => ArtworkService::rules(),
            'remove_artwork' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => str($this->string('title'))->slug()->toString()]);
        }
    }
}
