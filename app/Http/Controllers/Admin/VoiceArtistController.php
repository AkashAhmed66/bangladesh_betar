<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\VoiceArtist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * M11 — voice artist roster for marketing production (FR-MKT-01).
 */
class VoiceArtistController extends Controller
{
    public function index(Request $request): View
    {
        $artists = VoiceArtist::query()
            ->with('artist')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('availability'), fn ($q) => $q->where('is_available', $request->query('availability') === 'available'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.voice-artists.index', compact('artists'));
    }

    public function create(): View
    {
        $this->authorize('marketing.manage');

        return view('admin.voice-artists.form', ['voiceArtist' => null] + $this->options());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('marketing.manage');

        VoiceArtist::query()->create($this->validated($request));

        return redirect()->route('admin.voice-artists.index')->with('success', 'Voice artist added to roster.');
    }

    public function edit(VoiceArtist $voiceArtist): View
    {
        $this->authorize('marketing.manage');

        return view('admin.voice-artists.form', ['voiceArtist' => $voiceArtist] + $this->options());
    }

    public function update(Request $request, VoiceArtist $voiceArtist): RedirectResponse
    {
        $this->authorize('marketing.manage');

        $voiceArtist->update($this->validated($request));

        return redirect()->route('admin.voice-artists.index')->with('success', 'Voice artist profile updated.');
    }

    public function destroy(VoiceArtist $voiceArtist): RedirectResponse
    {
        $this->authorize('marketing.manage');

        $voiceArtist->delete();

        return redirect()->route('admin.voice-artists.index')->with('success', 'Voice artist removed from roster.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'artist_id' => ['nullable', 'exists:artists,id'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:10'],
            'age_range' => ['nullable', Rule::in(['child', 'young', 'adult', 'senior'])],
            'languages' => ['nullable', 'string', 'max:255'],
            'accent' => ['nullable', 'string', 'max:50'],
            'tone' => ['nullable', 'string', 'max:50'],
            'style' => ['nullable', 'string', 'max:50'],
            'is_available' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function options(): array
    {
        return [
            'artists' => Artist::query()->where('artist_type', 'voice_artist')->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
