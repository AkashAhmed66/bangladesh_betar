<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Audio Postmortem — duplicate / violence / anti-government detection +
    // transcription, run against every newly ingested audio asset (M16).
    'audio_postmortem' => [
        'base_url' => env('AUDIO_POSTMORTEM_BASE_URL', 'http://202.59.133.123:9026'),
        'timeout' => (int) env('AUDIO_POSTMORTEM_TIMEOUT', 30),
        'poll_interval_seconds' => (int) env('AUDIO_POSTMORTEM_POLL_INTERVAL', 10),
        'max_poll_attempts' => (int) env('AUDIO_POSTMORTEM_MAX_ATTEMPTS', 180), // ~30 min at 10s
    ],

    // LiveKit — self-hosted WebRTC SFU for live audio broadcasting (M27).
    //   host     — internal/server-side base URL (room + webhook APIs)
    //   ws_url   — browser-facing signalling URL handed to the client SDK
    //   api_key  / api_secret — used to mint join tokens and verify webhooks
    'livekit' => [
        'host' => env('LIVEKIT_HOST', 'http://localhost:7880'),
        // Browser-facing signalling URL. Leave empty to derive it from the
        // request host (ws://<host>:<ws_port>) so it works from any IP the
        // server is reached on. Set explicitly for a fixed wss:// domain.
        'ws_url' => env('LIVEKIT_WS_URL', ''),
        'ws_port' => (int) env('LIVEKIT_WS_PORT', 7880),
        'api_key' => env('LIVEKIT_API_KEY', 'devkey'),
        'api_secret' => env('LIVEKIT_API_SECRET'),
        'publisher_ttl_minutes' => (int) env('LIVEKIT_PUBLISHER_TTL', 360), // 6h broadcast max
        'listener_ttl_minutes' => (int) env('LIVEKIT_LISTENER_TTL', 180),   // 3h listen session
        // Where the public Next.js app lives — used to link broadcasters to the
        // listener-facing live page from the studio. Empty => derive from the
        // request host (http://<host>:<public_app_port>).
        'public_app_url' => env('PUBLIC_APP_URL', ''),
        'public_app_port' => (int) env('PUBLIC_APP_PORT', 9000),
    ],

];
