<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastRecording;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BroadcastRecordingController extends Controller
{
    /** Authorized, range-capable playback for the studio audio controls. */
    public function audio(BroadcastRecording $recording): BinaryFileResponse
    {
        $path = $this->absolutePath($recording);

        return response()->file($path, [
            'Content-Type' => $recording->mime_type ?: 'audio/ogg',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function download(BroadcastRecording $recording): BinaryFileResponse
    {
        $path = $this->absolutePath($recording);
        $session = $recording->session;
        $title = Str::slug($session?->title ?: 'broadcast-recording');
        $date = $session?->started_at?->format('Y-m-d-His') ?? (string) $recording->id;

        return response()->download($path, "{$title}-{$date}.ogg", [
            'Content-Type' => $recording->mime_type ?: 'audio/ogg',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function absolutePath(BroadcastRecording $recording): string
    {
        abort_unless($recording->isPlayable(), 404, 'This recording is not ready yet.');

        $disk = Storage::disk($recording->disk);
        abort_unless($recording->file_path && $disk->exists($recording->file_path), 404, 'The recording file is unavailable.');

        return $disk->path($recording->file_path);
    }
}
