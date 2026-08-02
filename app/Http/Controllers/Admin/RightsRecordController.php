<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioAsset;
use App\Models\RightsHolder;
use App\Models\RightsRecord;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * M14 — per-asset rights records. Publication is gated on the parent
 * asset's rights_status, which is kept in sync with the record (FR-CPR-04/05).
 */
class RightsRecordController extends Controller
{
    private const RIGHTS_TYPES = ['broadcast', 'streaming', 'download', 'commercial'];

    private const STATUSES = ['cleared', 'restricted', 'pending', 'disputed', 'expired'];

    public function index(Request $request): View
    {
        $records = RightsRecord::query()
            ->with(['audioAsset', 'rightsHolder'])
            ->when($request->boolean('expiring'), fn ($q) => $q->expiringWithin(90))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rights-records.index', [
            'records' => $records,
            'statuses' => self::STATUSES,
        ]);
    }

    public function create(): View
    {
        $this->authorize('rights.manage');

        return view('admin.rights-records.form', ['record' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('rights.manage');

        $record = RightsRecord::query()->create(
            $this->validated($request) + ['created_by' => $request->user()->id]
        );

        $this->syncAssetRightsStatus($record);

        return redirect()->route('admin.rights-records.index')->with('success', 'Rights record created.');
    }

    public function edit(RightsRecord $rightsRecord): View
    {
        $this->authorize('rights.manage');

        return view('admin.rights-records.form', ['record' => $rightsRecord] + $this->options());
    }

    public function update(Request $request, RightsRecord $rightsRecord): RedirectResponse
    {
        $this->authorize('rights.manage');

        $oldStatus = $rightsRecord->status;
        $rightsRecord->update($this->validated($request));

        $this->syncAssetRightsStatus($rightsRecord);

        // Tell the submitter/uploader when the clearance decision lands.
        if ($rightsRecord->status !== $oldStatus) {
            $asset = $rightsRecord->audioAsset;
            $recipients = collect([$rightsRecord->creator, $asset?->uploader])->filter();
            $cleared = $rightsRecord->status === 'cleared';

            Notify::users($recipients,
                $cleared ? 'publish_ready' : 'rights_status',
                $cleared ? 'Rights cleared — ready to publish' : 'Rights status updated',
                $cleared
                    ? "Rights for “{$asset?->title}” are cleared. You can now publish it to the public app."
                    : "Rights for “{$asset?->title}” were set to “{$rightsRecord->status}”.",
                $asset ? route('admin.assets.show', $asset) : route('admin.rights-records.index'),
                except: $request->user()->id);
        }

        return redirect()->route('admin.rights-records.index')->with('success', 'Rights record updated.');
    }

    public function destroy(RightsRecord $rightsRecord): RedirectResponse
    {
        $this->authorize('rights.manage');

        $rightsRecord->delete();

        return redirect()->route('admin.rights-records.index')->with('success', 'Rights record deleted.');
    }

    /**
     * Download a submitted copyright document (FR-CPR-02). Available to the
     * rights team and to the asset's submitter (record visibility) — the files
     * live on the private disk and are only served through this route.
     */
    public function document(RightsRecord $rightsRecord, int $index): StreamedResponse
    {
        $user = auth()->user();
        abort_unless(
            $user->can('rights.view') || ($rightsRecord->audioAsset?->isVisibleTo($user) ?? false),
            403,
        );

        $doc = $rightsRecord->documents[$index] ?? null;
        abort_unless($doc && Storage::disk('local')->exists($doc['path']), 404, 'Document not found.');

        return Storage::disk('local')->download($doc['path'], $doc['name'] ?? basename($doc['path']));
    }

    /**
     * Keep the parent asset's rights_status aligned with this record so the
     * publication gate (AudioAsset::scopePublished / isPublished) is accurate.
     */
    private function syncAssetRightsStatus(RightsRecord $record): void
    {
        AudioAsset::query()
            ->whereKey($record->audio_asset_id)
            ->update(['rights_status' => $record->status]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'audio_asset_id' => ['required', 'exists:audio_assets,id'],
            'rights_holder_id' => ['nullable', 'exists:rights_holders,id'],
            'rights_types' => ['required', 'array', 'min:1'],
            'rights_types.*' => [Rule::in(self::RIGHTS_TYPES)],
            'territory' => ['required', 'string', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'royalty_required' => ['boolean'],
            'royalty_notes' => ['nullable', 'string'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function options(): array
    {
        return [
            'assets' => AudioAsset::query()->orderByDesc('id')->take(300)->pluck('title', 'id'),
            'holders' => RightsHolder::query()->orderBy('name')->pluck('name', 'id'),
            'rightsTypes' => self::RIGHTS_TYPES,
            'statuses' => self::STATUSES,
        ];
    }
}
