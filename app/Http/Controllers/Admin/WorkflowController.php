<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * M13 — configurable multi-stage approval workflows per content type (FR-WRK-01).
 */
class WorkflowController extends Controller
{
    private const CONTENT_TYPES = ['song', 'podcast', 'advert', 'programme', 'historical', 'story', 'default'];

    public function index(): View
    {
        $workflows = Workflow::query()
            ->withCount('stages')
            ->orderBy('content_type')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.workflows.index', compact('workflows'));
    }

    public function create(): View
    {
        $this->authorize('workflows.manage');

        return view('admin.workflows.form', ['workflow' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('workflows.manage');

        $data = $this->validated($request);
        $stages = $data['stages'];
        unset($data['stages']);

        DB::transaction(function () use ($data, $stages): void {
            $workflow = Workflow::query()->create($data);
            $this->syncStages($workflow, $stages);
        });

        return redirect()->route('admin.workflows.index')->with('success', 'Workflow created.');
    }

    public function edit(Workflow $workflow): View
    {
        $this->authorize('workflows.manage');

        $workflow->load('stages');

        return view('admin.workflows.form', ['workflow' => $workflow] + $this->options());
    }

    public function update(Request $request, Workflow $workflow): RedirectResponse
    {
        $this->authorize('workflows.manage');

        $data = $this->validated($request);
        $stages = $data['stages'];
        unset($data['stages']);

        DB::transaction(function () use ($workflow, $data, $stages): void {
            $workflow->update($data);
            $this->syncStages($workflow, $stages);
        });

        return redirect()->route('admin.workflows.index')->with('success', 'Workflow updated.');
    }

    public function destroy(Workflow $workflow): RedirectResponse
    {
        $this->authorize('workflows.manage');

        $workflow->delete();

        return redirect()->route('admin.workflows.index')->with('success', 'Workflow deleted.');
    }

    /** Recreate stage rows, deriving sequence from submitted order. */
    private function syncStages(Workflow $workflow, array $stages): void
    {
        $workflow->stages()->delete();

        foreach (array_values($stages) as $index => $stage) {
            $workflow->stages()->create([
                'name' => $stage['name'],
                'approver_role' => $stage['approver_role'],
                'sequence' => $index + 1,
            ]);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content_type' => ['required', Rule::in(self::CONTENT_TYPES)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'escalation_hours' => ['required', 'integer', 'min:1'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.name' => ['required', 'string', 'max:255'],
            'stages.*.approver_role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);
    }

    private function options(): array
    {
        return [
            'contentTypes' => self::CONTENT_TYPES,
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ];
    }
}
