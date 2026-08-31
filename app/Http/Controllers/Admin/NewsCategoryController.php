<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class NewsCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.portal-categories.index', [
            'portal' => 'news',
            'title' => 'News Categories',
            'categories' => NewsCategory::query()->with(['parent', 'children'])->withCount('articles')
                ->orderByRaw('parent_id is not null')->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.portal-categories.form', [
            'portal' => 'news', 'title' => 'Create News Category', 'category' => null,
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['show_in_header'] = false;
        NewsCategory::query()->create($data);

        return redirect()->route('admin.news-categories.index')->with('success', 'News category created.');
    }

    public function edit(NewsCategory $newsCategory): View
    {
        return view('admin.portal-categories.form', [
            'portal' => 'news', 'title' => 'Edit News Category', 'category' => $newsCategory,
            'parentOptions' => $this->parentOptions($newsCategory),
        ]);
    }

    public function update(Request $request, NewsCategory $newsCategory): RedirectResponse
    {
        $data = $this->validated($request, $newsCategory);
        $data['show_in_header'] = $newsCategory->isFixedHeader();

        if ($newsCategory->isFixedHeader()) {
            $data['slug'] = $newsCategory->getRawOriginal('slug');
            $data['position'] = $newsCategory->fixedHeaderPosition();
            $data['is_active'] = true;
            $data['parent_id'] = null;
        } elseif (filled($data['parent_id'] ?? null) && $newsCategory->children()->exists()) {
            throw ValidationException::withMessages(['parent_id' => 'A category with subcategories cannot itself become a subcategory.']);
        }

        $newsCategory->update($data);
        $newsCategory->articles()->update(['category' => $newsCategory->name]);

        return redirect()->route('admin.news-categories.index')->with('success', 'News category updated everywhere.');
    }

    public function destroy(NewsCategory $newsCategory): RedirectResponse
    {
        if ($newsCategory->isFixedHeader()) {
            return back()->with('error', __('Fixed News header categories cannot be deleted.'));
        }

        if ($newsCategory->articles()->exists()) {
            return back()->with('error', 'Move its articles to another category before deleting it.');
        }
        if ($newsCategory->children()->exists()) {
            return back()->with('error', 'Move or delete its subcategories before deleting this category.');
        }
        $newsCategory->delete();

        return back()->with('success', 'News category deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?NewsCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('news_categories')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'description_bn' => ['nullable', 'string', 'max:1000'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'show_in_header' => ['sometimes', 'boolean'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('news_categories', 'id')->where(fn ($query) => $query->whereNull('parent_id')->where('is_active', true)),
                Rule::notIn(array_filter([$category?->id])),
            ],
        ]);
    }

    /** @return array<int, string> */
    private function parentOptions(?NewsCategory $editing = null): array
    {
        return NewsCategory::query()->active()->whereNull('parent_id')
            ->when($editing, fn ($query) => $query->whereKeyNot($editing->id))
            ->orderBy('position')->orderBy('name')->pluck('name', 'id')->all();
    }
}
