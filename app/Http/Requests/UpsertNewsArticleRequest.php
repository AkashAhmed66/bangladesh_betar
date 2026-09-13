<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\NewsArticle;
use App\Services\ArtworkService;
use App\Support\YouTube;
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
        $leadImageRequired = ! ($article instanceof NewsArticle)
            || ! $article->image_path
            || ($this->boolean('remove_artwork') && ! $this->hasFile('artwork'));

        return [
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('news_articles', 'slug')->ignore($article instanceof NewsArticle ? $article->id : null)],
            'summary' => ['required', 'string', 'max:2000'],
            'summary_bn' => ['nullable', 'string', 'max:2000'],
            'news_category_id' => ['nullable', 'required_without:category', 'integer', Rule::exists('news_categories', 'id')->where('is_active', true)],
            'category' => ['nullable', 'required_without:news_category_id', 'string', Rule::exists('news_categories', 'name')],
            'body_text' => ['required', 'string'],
            'body_text_bn' => ['nullable', 'string'],
            'read_time_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_featured' => ['required', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'artwork' => ArtworkService::rules($leadImageRequired),
            'remove_artwork' => ['boolean'],
            'images' => ['nullable', 'array', 'max:30'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'videos' => ['nullable', 'array', 'max:20'],
            'videos.*' => ['file', 'mimes:mp4,webm,mov,m4v', 'max:102400'],
            'audios' => ['nullable', 'array', 'max:30'],
            'audios.*' => [
                'bail',
                'file',
                'extensions:mp3,wav,ogg,m4a,aac,flac',
                'mimetypes:audio/*,video/mp4,application/mp4,application/ogg,application/x-ogg,application/x-flac',
                'max:51200',
            ],
            'documents' => ['nullable', 'array', 'max:30'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt', 'max:25600'],
            'youtube_links' => ['nullable', 'array', 'max:20'],
            'youtube_links.*' => [
                'nullable',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && filled($value) && YouTube::videoId($value) === null) {
                        $fail('Enter a valid YouTube video URL. YouTube watch, Shorts, live, embed and youtu.be links are supported.');
                    }
                },
            ],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => [
                'integer',
                Rule::exists('news_article_media', 'id')->where(
                    'news_article_id',
                    $article instanceof NewsArticle ? $article->id : 0,
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'audios.*.extensions' => 'Each audio file must use one of these extensions: MP3, WAV, OGG, M4A, AAC, or FLAC.',
            'audios.*.mimetypes' => 'Each upload must contain valid MP3, WAV, OGG, M4A, AAC, or FLAC audio.',
            'youtube_links.max' => 'You may add up to 20 YouTube videos to one article.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge(['slug' => str($this->string('title'))->slug()->toString()]);
        }
    }
}
