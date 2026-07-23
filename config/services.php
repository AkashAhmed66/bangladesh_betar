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
        'max_poll_attempts' => (int) env('AUDIO_POSTMORTEM_MAX_ATTEMPTS', 60), // ~10 min at 10s
    ],

];
