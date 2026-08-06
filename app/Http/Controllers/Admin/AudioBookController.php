<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAudioBook;
use App\Models\AudioBook;
use App\Models\AuditLog;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * M31 — Audio Books. Creators (artists + content staff) convert a PDF or
 * pasted text into narrated audio in BOTH male and female voices, submit it
 * for approval, and approvers publish it to premium listeners in the public
 * app (read-along text + audio).
 */
class AudioBookController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $books = AudioBook::query()
            ->with(['user', 'approver'])
            // Creators see their own; records.view-all and approvers see everything.
            ->when(
                ! $user->can('records.view-all') && ! $user->can('audiobooks.approve'),
                fn ($q) => $q->where('user_id', $user->id),
            )
            ->latest()
            ->paginate(12);

        return view('admin.audiobooks.index', compact('books'));
    }

    public function create(): View
    {
        return view('admin.audiobooks.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'language' => ['required', Rule::in(['auto', 'en', 'bn'])],
            'pdf' => ['nullable', 'required_without:text', 'file', 'mimes:pdf', 'max:51200'],
            'text' => ['nullable', 'required_without:pdf', 'string', 'max:120000'],
        ]);

        $book = AudioBook::query()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'language' => $data['language'],
            'source_type' => $request->hasFile('pdf') ? 'pdf' : 'text',
            'text' => $data['text'] ?? null,
            'status' => 'generating',
        ]);

        if ($request->hasFile('pdf')) {
            $book->update([
                'source_path' => $request->file('pdf')->storeAs('speech/audiobooks/sources', "{$book->id}.pdf", 'local'),
            ]);
        }

        GenerateAudioBook::dispatch($book->id);

        return redirect()->route('admin.audiobooks.index')
            ->with('success', 'Audio book queued — both narrations are being generated. You will get a notification when it is ready to review and submit.');
    }

    /** Review page: full details, both players, the text, and the decision. */
    public function show(Request $request, AudioBook $audiobook): View
    {
        $this->authorizeBookAccess($request, $audiobook);

        $audiobook->load(['user', 'approver']);

        return view('admin.audiobooks.show', ['book' => $audiobook]);
    }

    /** Creator sends a ready book into approval. */
    public function submit(Request $request, AudioBook $audiobook): RedirectResponse
    {
        $this->authorizeBookAccess($request, $audiobook);

        abort_unless($audiobook->isReadyForSubmission(), 404, 'This audio book is not ready for submission.');

        $audiobook->update(['status' => 'pending_approval', 'submitted_at' => now()]);

        AuditLog::record('audiobook_submitted', $audiobook, null, null,
            "Audio book “{$audiobook->title}” submitted for approval.");

        Notify::permission('audiobooks.approve', 'needs_approval',
            'Audio book needs your approval',
            "{$request->user()->name} submitted the audio book “{$audiobook->title}” (".($audiobook->language === 'bn' ? 'Bangla' : 'English').', male + female narrations).',
            route('admin.audiobooks.show', $audiobook),
            except: $request->user()->id);

        return back()->with('success', 'Submitted for approval — approvers have been notified.');
    }

    /** Approve (→ published in the public app) or reject, with remarks. */
    public function review(Request $request, AudioBook $audiobook): RedirectResponse
    {
        $this->authorize('audiobooks.approve');

        abort_unless($audiobook->status === 'pending_approval', 404, 'This audio book is not awaiting approval.');

        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'comments' => ['required', 'string', 'max:2000'],
        ], [
            'comments.required' => 'A remark is required to record this decision.',
        ]);

        $approved = $data['action'] === 'approve';

        $audiobook->update([
            'status' => $approved ? 'published' : 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'review_comments' => $data['comments'],
            'published_at' => $approved ? ($audiobook->published_at ?? now()) : $audiobook->published_at,
        ]);

        AuditLog::record(
            $approved ? 'audiobook_approved' : 'audiobook_rejected',
            $audiobook,
            ['status' => 'pending_approval'],
            ['status' => $audiobook->status, 'remarks' => $data['comments']],
            'Audio book “'.$audiobook->title.'” '.($approved ? 'approved and published' : 'rejected').'.',
        );

        Notify::user(
            $audiobook->user_id === $request->user()->id ? null : $audiobook->user,
            $approved ? 'approved' : 'rejected',
            $approved ? 'Audio book published' : 'Audio book rejected',
            $approved
                ? "“{$audiobook->title}” was approved and is now live for premium listeners in the public app."
                : "“{$audiobook->title}” was rejected: {$data['comments']}",
            route('admin.audiobooks.show', $audiobook),
        );

        return back()->with('success', $approved
            ? 'Approved — the audio book is now live for premium listeners.'
            : 'Rejected — the creator has been notified.');
    }

    /** Take a live audio book off the public app. It can be resubmitted later. */
    public function unpublish(Request $request, AudioBook $audiobook): RedirectResponse
    {
        $this->authorize('audiobooks.approve');

        abort_unless($audiobook->status === 'published', 404, 'This audio book is not published.');

        $audiobook->update(['status' => 'unpublished']);

        AuditLog::record('audiobook_unpublished', $audiobook,
            ['status' => 'published'], ['status' => 'unpublished'],
            "Audio book “{$audiobook->title}” removed from the public app.");

        Notify::user(
            $audiobook->user_id === $request->user()->id ? null : $audiobook->user,
            'rights_status',
            'Audio book unpublished',
            "“{$audiobook->title}” was removed from the public app. It can be revised and resubmitted.",
            route('admin.audiobooks.show', $audiobook),
        );

        return back()->with('success', 'Audio book removed from the public app.');
    }

    /** Admin preview stream (male|female|enhanced). */
    public function audio(Request $request, AudioBook $audiobook, string $voice): BinaryFileResponse
    {
        $this->authorizeBookAccess($request, $audiobook);
        abort_unless(in_array($voice, ['male', 'female', 'enhanced'], true), 404);

        $path = match ($voice) {
            'male' => $audiobook->audio_male_path,
            'female' => $audiobook->audio_female_path,
            'enhanced' => $audiobook->audio_enhanced_path,
        };
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404, 'Audio not available.');

        return response()->file($disk->path($path), [
            'Content-Type' => str_ends_with($path, '.mp3') ? 'audio/mpeg' : 'audio/wav',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Edit the book text and regenerate the narration from it. The edited
     * text becomes authoritative (PDF re-extraction is skipped from now on).
     * Editable in EVERY state except mid-generation: editing a published
     * book takes it off the public app, editing a pending one withdraws the
     * submission — either way the book must pass approval again via Submit.
     */
    public function updateText(Request $request, AudioBook $audiobook): RedirectResponse
    {
        $user = $request->user();
        abort_unless($audiobook->user_id === $user->id || $user->can('records.view-all'), 403);
        // Only the brief generation window is blocked — two concurrent jobs
        // would race on the same narration files.
        abort_unless(
            $audiobook->status !== 'generating',
            422,
            'The narration is being generated right now — wait for it to finish, then edit.',
        );

        $data = $request->validate([
            'text' => ['required', 'string', 'min:5', 'max:120000'],   // GenerateAudioBook::MAX_CHARS
        ]);

        $old = (string) $audiobook->text;
        $wasLive = $audiobook->status === 'published';
        $wasPending = $audiobook->status === 'pending_approval';

        $audiobook->update([
            'text' => $data['text'],
            'characters' => mb_strlen($data['text']),
            'text_edited' => true,
            'status' => 'generating',
            'error' => null,
        ]);

        try {
            $note = $wasLive ? ' The book was withdrawn from the public app.'
                : ($wasPending ? ' The pending approval request was withdrawn.' : '');
            AuditLog::record('audiobook_text_edited', $audiobook, null, [
                'old_length' => mb_strlen($old),
                'new_length' => mb_strlen($data['text']),
                'old' => $old,
                'new' => $data['text'],
            ], "Audio book “{$audiobook->title}” text edited by {$user->name} — narration regenerating; approval required again.{$note}");
        } catch (\Throwable) {
            // the edit must never fail because the audit write did
        }

        GenerateAudioBook::dispatch($audiobook->id);

        $flash = $wasLive
            ? 'Text saved — the book is off the public app while the narration regenerates. Submit for approval once it is ready.'
            : 'Text saved — the narration is being regenerated. Submit for publication once it is ready.';

        return redirect()->route('admin.audiobooks.show', $audiobook)->with('success', $flash);
    }

    public function destroy(Request $request, AudioBook $audiobook): RedirectResponse
    {
        $user = $request->user();
        abort_unless($audiobook->user_id === $user->id || $user->can('records.view-all'), 403);

        foreach ([$audiobook->source_path, $audiobook->audio_male_path, $audiobook->audio_female_path, $audiobook->audio_enhanced_path] as $path) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
        }
        \App\Support\Hls::delete('audiobook', $audiobook->id);
        $audiobook->delete();

        return redirect()->route('admin.audiobooks.index')->with('success', 'Audio book removed.');
    }

    /** Creator, records.view-all holders and approvers may open a book. */
    private function authorizeBookAccess(Request $request, AudioBook $book): void
    {
        $user = $request->user();
        abort_unless(
            $book->user_id === $user->id
                || $user->can('records.view-all')
                || $user->can('audiobooks.approve'),
            403,
        );
    }
}
