<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WatchCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class WatchCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.portal-categories.index', [
            'portal' => 'watch',
            'title' => 'Watch Categories',
            'categories' => WatchCategory::query()->withCount('shows')->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.portal-categories.form', ['portal' => 'watch', 'title' => 'Create Watch Category', 'category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        WatchCategory::query()->create($this->validated($request));

        return redirect()->route('admin.watch-categories.index')->with('success', 'Watch category created.');
    }

    public function edit(WatchCategory $watchCategory): View
    {
        return view('admin.portal-categories.form', ['portal' => 'watch', 'title' => 'Edit Watch Category', 'category' => $watchCategory]);
    }

    public function update(Request $request, WatchCategory $watchCategory): RedirectResponse
    {
        $watchCategory->update($this->validated($request, $watchCategory));
        $watchCategory->shows()->update(['category' => $watchCategory->name]);

        return redirect()->route('admin.watch-categories.index')->with('success', 'Watch category updated everywhere.');
    }

    public function destroy(WatchCategory $watchCategory): RedirectResponse
    {
        if ($watchCategory->shows()->exists()) {
            return back()->with('error', 'Move its shows to another category before deleting it.');
        }
        $watchCategory->delete();

        return back()->with('success', 'Watch category deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?WatchCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('watch_categories')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'description_bn' => ['nullable', 'string', 'max:1000'],
            'position' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'show_in_header' => ['required', 'boolean'],
        ]);
    }
}
