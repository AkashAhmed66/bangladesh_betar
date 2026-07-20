<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\MediaItem;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M03 — digitization register for physical media.
 */
class MediaItemController extends Controller
{
    public function index(Request $request): View
    {
        $items = MediaItem::query()
            ->with(['station', 'audioAsset', 'digitizedBy'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->string('q').'%')
                ->orWhere('item_code', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('media_type'), fn ($q) => $q->where('media_type', $request->string('media_type')))
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderBy('item_code')
            ->paginate(15)
            ->withQueryString();

        $progress = MediaItem::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        return view('admin.media-items.index', compact('items', 'progress'));
    }

    public function create(): View
    {
        $this->authorize('digitization.manage');

        return view('admin.media-items.form', ['item' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('digitization.manage');

        MediaItem::query()->create($this->validated($request) + ['registered_by' => $request->user()->id]);

        return redirect()->route('admin.media-items.index')->with('success', 'Physical media item registered.');
    }

    public function edit(MediaItem $mediaItem): View
    {
        $this->authorize('digitization.manage');

        return view('admin.media-items.form', ['item' => $mediaItem] + $this->options());
    }

    public function update(Request $request, MediaItem $mediaItem): RedirectResponse
    {
        $this->authorize('digitization.manage');

        $data = $this->validated($request, $mediaItem->id);

        if (in_array($data['status'], ['captured', 'restored', 'archived'], true) && $mediaItem->digitized_at === null) {
            $data['digitized_by'] = $request->user()->id;
            $data['digitized_at'] = now();
        }

        $mediaItem->update($data);

        return redirect()->route('admin.media-items.index')->with('success', 'Digitization record updated.');
    }

    public function destroy(MediaItem $mediaItem): RedirectResponse
    {
        $this->authorize('digitization.manage');

        $mediaItem->delete();

        return redirect()->route('admin.media-items.index')->with('success', 'Media item removed from register.');
    }

    private function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'item_code' => ['required', 'string', 'max:40', 'unique:media_items,item_code'.($ignore ? ",$ignore" : '')],
            'title' => ['required', 'string', 'max:255'],
            'media_type' => ['required', Rule::in(['reel', 'cassette', 'dat', 'vinyl', 'cd', 'minidisc'])],
            'condition' => ['required', Rule::in(['good', 'fair', 'poor', 'critical'])],
            'location' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', Rule::in(['high', 'medium', 'low'])],
            'status' => ['required', Rule::in(['registered', 'in_progress', 'captured', 'restored', 'qc_pending', 'qc_passed', 'archived', 'failed'])],
            'station_id' => ['nullable', 'exists:stations,id'],
            'audio_asset_id' => ['nullable', 'exists:audio_assets,id'],
            'restoration_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function options(): array
    {
        return [
            'stations' => Station::query()->orderBy('name')->pluck('name', 'id'),
            'assets' => AudioAsset::query()->orderByDesc('id')->take(200)->pluck('title', 'id'),
        ];
    }
}
