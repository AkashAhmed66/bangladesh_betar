<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastRecording;
use App\Support\Hls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class BroadcastRecordingController extends Controller
{
    public function publish(BroadcastRecording $recording): RedirectResponse
    {
        abort_unless($recording->isPlayable(), 422, 'Only completed recordings can be published.');
        abort_unless(
            $recording->file_path && Storage::disk($recording->disk)->exists($recording->file_path),
            404,
            'The recording file is unavailable.',
        );

        $recording->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        Hls::ensureQueued('broadcast', $recording->id, 'main');

        return back()->with('success', 'Broadcast recording published for Premium listeners.');
    }

    public function unpublish(BroadcastRecording $recording): RedirectResponse
    {
        $recording->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return back()->with('success', 'Broadcast recording removed from the public portal.');
    }

    public function destroy(BroadcastRecording $recording): RedirectResponse
    {
        abort_if(
            in_array($recording->status, ['pending', 'starting', 'active', 'finalizing'], true),
            422,
            'Stop and finalize this recording before deleting it.',
        );

        $disk = Storage::disk($recording->disk);
        if ($recording->file_path && $disk->exists($recording->file_path)) {
            $disk->delete($recording->file_path);
            abort_if($disk->exists($recording->file_path), 500, 'The recording file could not be deleted.');
        }

        Hls::delete('broadcast', $recording->id);
        $recording->delete();

        return back()->with('success', 'Broadcast recording permanently deleted.');
    }
}
