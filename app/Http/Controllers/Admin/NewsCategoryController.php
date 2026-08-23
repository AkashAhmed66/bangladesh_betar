<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class NewsCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.portal-categories.index', [
            'portal' => 'news',
            'title' => 'News Categories',
            'categories' => NewsCategory::query()->withCount('articles')->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.portal-categories.form', ['portal' => 'news', 'title' => 'Create News Category', 'category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        NewsCategory::query()->create($this->validated($request));

        return redirect()->route('admin.news-categories.index')->with('success', 'News category created.');
    }

    public function edit(NewsCategory $newsCategory): View
    {
        return view('admin.portal-categories.form', ['portal' => 'news', 'title' => 'Edit News Category', 'category' => $newsCategory]);
    }

    public function update(Request $request, NewsCategory $newsCategory): RedirectResponse
    {
        $newsCategory->update($this->validated($request, $newsCategory));
        $newsCategory->articles()->update(['category' => $newsCategory->name]);

        return redirect()->route('admin.news-categories.index')->with('success', 'News category updated everywhere.');
    }

    public function destroy(NewsCategory $newsCategory): RedirectResponse
    {
        if ($newsCategory->articles()->exists()) {
            return back()->with('error', 'Move its articles to another category before deleting it.');
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
            'show_in_header' => ['required', 'boolean'],
        ]);
    }
}
