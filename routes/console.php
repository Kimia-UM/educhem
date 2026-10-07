<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Chat fallbacks are processed first, followed by answer evaluations. The
// legacy `ai` queue remains last until jobs created by older releases drain.
// Keep the scheduled worker alive for most of each minute. This remains a
// bounded cron process (not a daemon), while avoiding up to one minute of
// extra latency when the queue is empty at the exact cron boundary.
Schedule::command('queue:work database --queue=ai-chat,ai-evaluation,ai --max-time=50 --sleep=1 --tries=3 --timeout=120')
    ->everyMinute()
    ->withoutOverlapping(10);

Schedule::command('queue:prune-failed --hours=168')
    ->daily()
    ->withoutOverlapping();
