<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\FetchFinancialNewsJob;
use App\Jobs\FetchGeopoliticalNewsJob;
use App\Jobs\FetchMarketNewsJob;
use App\Jobs\FetchCalendarJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ──────────────────────────────────────────────
// News Ingestion Scheduler
// ──────────────────────────────────────────────

// Financial news: every 30 minutes
Schedule::job(new FetchFinancialNewsJob)
    ->everyThirtyMinutes()
    ->name('ingest:financial')
    ->withoutOverlapping()
    ->onOneServer();

// Geopolitical news: every hour
Schedule::job(new FetchGeopoliticalNewsJob)
    ->hourly()
    ->name('ingest:geopolitical')
    ->withoutOverlapping()
    ->onOneServer();

// Market news: every 30 minutes
Schedule::job(new FetchMarketNewsJob)
    ->everyThirtyMinutes()
    ->name('ingest:market')
    ->withoutOverlapping()
    ->onOneServer();

// Economic calendar: every 6 hours
Schedule::job(new FetchCalendarJob)
    ->everySixHours()
    ->name('ingest:economic_calendar')
    ->withoutOverlapping()
    ->onOneServer();

// Process economic events: classify + compute surprise every hour
Schedule::command('events:process')
    ->hourly()
    ->name('events:process')
    ->withoutOverlapping()
    ->onOneServer();

// Process geopolitical events: classify + detect regions + dispatch AI analysis every 2 hours
Schedule::command('events:geopolitical')
    ->everyTwoHours()
    ->name('events:geopolitical')
    ->withoutOverlapping()
    ->onOneServer();

// Fetch market asset prices every thirty minutes
Schedule::command('market:fetch-prices')
    ->everyThirtyMinutes()
    ->name('market:fetch-prices')
    ->withoutOverlapping()
    ->onOneServer();
