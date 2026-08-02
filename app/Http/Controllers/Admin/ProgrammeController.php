<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Programme;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgrammeController extends Controller
{
    public function index(Request $request): View
    {
        $programmes = Programme::query()
            ->visibleTo($request->user())
            ->with(['station', 'category'])
            ->withCount(['episodes', 'audioAssets'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('admin.programmes.index', compact('programmes'));
    }

    public function create(): View
    {
        $this->authorize('programmes.manage');

        return view('admin.programmes.form', ['programme' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('programmes.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']);

        Programme::query()->create($data);

        return redirect()->route('admin.programmes.index')->with('success', 'Programme created.');
    }

    public function edit(Programme $programme): View
    {
        $this->authorize('programmes.manage');
        $this->authorizeRecordVisibility($programme);

        return view('admin.programmes.form', ['programme' => $programme] + $this->options());
    }

    public function update(Request $request, Programme $programme): RedirectResponse
    {
        $this->authorize('programmes.manage');
        $this->authorizeRecordVisibility($programme);

        $programme->update($this->validated($request));

        return redirect()->route('admin.programmes.index')->with('success', 'Programme updated.');
    }

    public function destroy(Programme $programme): RedirectResponse
    {
        $this->authorize('programmes.manage');
        $this->authorizeRecordVisibility($programme);

        $programme->delete();

        return redirect()->route('admin.programmes.index')->with('success', 'Programme removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'programme_type' => ['required', Rule::in(['programme', 'drama', 'news', 'event', 'magazine', 'talk_show'])],
            'station_id' => ['nullable', 'exists:stations,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);
    }

    private function options(): array
    {
        return [
            'stations' => Station::query()->orderBy('name')->pluck('name', 'id'),
            'categories' => Category::query()->where('type', 'content')->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
