<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Script;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M11 — advertising scripts with version control (FR-MKT-04).
 */
class ScriptController extends Controller
{
    private const STATUSES = ['draft', 'approved', 'archived'];

    public function index(Request $request): View
    {
        $scripts = Script::query()
            ->with(['creator', 'parent'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.scripts.index', ['scripts' => $scripts, 'statuses' => self::STATUSES]);
    }

    public function create(): View
    {
        $this->authorize('marketing.manage');

        return view('admin.scripts.form', ['script' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('marketing.manage');

        Script::query()->create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('admin.scripts.index')->with('success', 'Script created.');
    }

    public function edit(Script $script): View
    {
        $this->authorize('marketing.manage');

        return view('admin.scripts.form', ['script' => $script] + $this->options($script->id));
    }

    public function update(Request $request, Script $script): RedirectResponse
    {
        $this->authorize('marketing.manage');

        $script->update($this->validated($request));

        return redirect()->route('admin.scripts.index')->with('success', 'Script updated.');
    }

    public function destroy(Script $script): RedirectResponse
    {
        $this->authorize('marketing.manage');

        $script->delete();

        return redirect()->route('admin.scripts.index')->with('success', 'Script deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'version_number' => ['required', 'integer', 'min:1'],
            'parent_script_id' => ['nullable', 'exists:scripts,id'],
            'status' => ['required', Rule::in(self::STATUSES)],
        ]);
    }

    private function options(?int $ignore = null): array
    {
        return [
            'parents' => Script::query()
                ->when($ignore, fn ($q) => $q->whereKeyNot($ignore))
                ->orderBy('title')
                ->pluck('title', 'id'),
            'statuses' => self::STATUSES,
        ];
    }
}
