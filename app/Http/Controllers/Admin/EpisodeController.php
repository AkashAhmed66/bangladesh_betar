<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\Episode;
use App\Models\Programme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * M10 — event-programme episodes (e.g. Bhoot FM).
 */
class EpisodeController extends Controller
{
    public function index(Request $request): View
    {
        $episodes = Episode::query()
            ->with(['programme', 'audioAsset'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('programme'), fn ($q) => $q->where('programme_id', $request->integer('programme')))
            ->orderByDesc('broadcast_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.episodes.index', compact('episodes') + $this->options());
    }

    public function create(): View
    {
        $this->authorize('episodes.manage');

        return view('admin.episodes.form', ['episode' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('episodes.manage');

        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['title']).'-'.uniqid();

        Episode::query()->create($data);

        return redirect()->route('admin.episodes.index')->with('success', 'Episode created.');
    }

    public function edit(Episode $episode): View
    {
        $this->authorize('episodes.manage');

        return view('admin.episodes.form', ['episode' => $episode] + $this->options());
    }

    public function update(Request $request, Episode $episode): RedirectResponse
    {
        $this->authorize('episodes.manage');

        $episode->update($this->validated($request));

        return redirect()->route('admin.episodes.index')->with('success', 'Episode updated.');
    }

    public function destroy(Episode $episode): RedirectResponse
    {
        $this->authorize('episodes.manage');

        $episode->delete();

        return redirect()->route('admin.episodes.index')->with('success', 'Episode removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'programme_id' => ['required', 'exists:programmes,id'],
            'season_number' => ['required', 'integer', 'min:1'],
            'audio_asset_id' => ['nullable', 'exists:audio_assets,id'],
            'number' => ['nullable', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'broadcast_date' => ['nullable', 'date'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'is_published' => ['boolean'],
        ]);
    }

    private function options(): array
    {
        return [
            'programmes' => Programme::query()->orderBy('title')->pluck('title', 'id'),
            'assets' => AudioAsset::query()->orderByDesc('id')->take(200)->pluck('title', 'id'),
        ];
    }
}
