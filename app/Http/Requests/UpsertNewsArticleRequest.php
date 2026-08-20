<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NewsCategory;
use App\Models\NewsArticle;
use App\Services\ArtworkService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertNewsArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('news.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $article = $this->route('news_article');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('news_articles', 'slug')->ignore($article instanceof NewsArticle ? $article->id : null)],
            'summary' => ['required', 'string', 'max:2000'],
            'category' => ['required', Rule::in(array_column(NewsCategory::cases(), 'value'))],
            'body_text' => ['required', 'string'],
            'read_time_minutes' => ['required', 'integer', 'min:1', 'max:120'],
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
