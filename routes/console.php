<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// M06 — nightly reconcile of the Elasticsearch search indices. Real-time sync
// is handled by the Scout model observers; this refreshes every document and
// catches any drift. Runs at a low-traffic hour, never overlapping itself.
Schedule::command('search:index')
    ->dailyAt('03:15')
    ->withoutOverlapping()
    ->runInBackground();
