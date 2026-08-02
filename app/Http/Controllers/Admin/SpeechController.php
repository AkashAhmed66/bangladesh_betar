<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SynthesizeSpeech;
use App\Models\SpeechConversion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * M31 — offline PDF/text → speech (English + Bangla, male/female) using
 * poppler + Tesseract for extraction and espeak-ng for synthesis. Everything
 * runs inside this container; no external services.
 */
class SpeechController extends Controller
{
    public function index(Request $request): View
    {
        $conversions = SpeechConversion::query()
            ->with('user')
            // Own conversions only, unless the role sees all records.
            ->when(! $request->user()->can('records.view-all'), fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(12);

        return view('admin.speech.index', compact('conversions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'language' => ['required', Rule::in(['auto', 'en', 'bn'])],
            'voice' => ['required', Rule::in(['male', 'female'])],
            'pdf' => ['nullable', 'required_without:text', 'file', 'mimes:pdf', 'max:51200'],
            'text' => ['nullable', 'required_without:pdf', 'string', 'max:120000'],
        ]);

        $conversion = SpeechConversion::query()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'source_type' => $request->hasFile('pdf') ? 'pdf' : 'text',
            'language' => $data['language'],
            'voice' => $data['voice'],
            'source_text' => $data['text'] ?? null,
            'status' => 'queued',
        ]);

        if ($request->hasFile('pdf')) {
            $conversion->update([
                'source_path' => $request->file('pdf')->storeAs('speech/sources', "{$conversion->id}.pdf", 'local'),
            ]);
        }

        SynthesizeSpeech::dispatch($conversion->id);

        return back()->with('success', 'Conversion queued — you will get a notification when the speech is ready.');
    }

    /** Stream/download the generated audio (owner or records.view-all). */
    public function audio(Request $request, SpeechConversion $conversion): StreamedResponse
    {
        abort_unless(
            $conversion->user_id === $request->user()->id || $request->user()->can('records.view-all'),
            403,
        );
        abort_unless($conversion->output_path && Storage::disk('local')->exists($conversion->output_path), 404);

        return Storage::disk('local')->download(
            $conversion->output_path,
            \Illuminate\Support\Str::slug($conversion->title).'.'.pathinfo($conversion->output_path, PATHINFO_EXTENSION),
            ['Content-Type' => str_ends_with($conversion->output_path, '.mp3') ? 'audio/mpeg' : 'audio/wav'],
        );
    }

    public function destroy(Request $request, SpeechConversion $conversion): RedirectResponse
    {
        abort_unless(
            $conversion->user_id === $request->user()->id || $request->user()->can('records.view-all'),
            403,
        );

        foreach ([$conversion->source_path, $conversion->output_path] as $path) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        }
        $conversion->delete();

        return back()->with('success', 'Conversion removed.');
    }
}
