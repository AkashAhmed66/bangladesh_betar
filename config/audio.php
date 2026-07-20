<?php

return [
    /*
    | Directory that contains the ffmpeg/ffprobe binaries. Leave null to use
    | the system PATH (the Docker image installs them there). On a dev host
    | without ffmpeg, ingestion falls back to the native PHP WAV analyzer.
    */
    'ffmpeg_path' => env('FFMPEG_PATH'),

    /* Upload limits for single-file ingestion (M02). */
    'max_upload_mb' => env('AUDIO_MAX_UPLOAD_MB', 512),

    'accepted_formats' => ['wav', 'bwf', 'flac', 'mp3', 'aac', 'm4a', 'ogg', 'oga', 'aiff', 'aif'],
];
