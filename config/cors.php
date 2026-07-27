<?php

return [
    /*
     * Mirrors Laravel's default CORS config (permissive API access from the
     * separate Next.js portal origin), with one addition: Content-Length is
     * exposed cross-origin so the public app can show download progress for
     * offline saves (GET /api/v1/assets/{asset}/download).
     */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Length'],

    'max_age' => 0,

    'supports_credentials' => false,
];
