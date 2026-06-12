<?php

use App\Jobs\Analysis\AnalyzePendingStoriesJob;
use App\Jobs\Articles\GenerateDailyBriefingJob;
use App\Jobs\Articles\GenerateEligibleArticlesJob;
use App\Jobs\Articles\RefreshDevelopingArticlesJob;
use App\Jobs\Calendar\SyncEconomicCalendarJob;
use App\Jobs\Ingestion\DispatchSourceIngestionJob;
use App\Jobs\Maintenance\MaintainSeoJob;
use App\Jobs\Market\CaptureMarketPricesJob;
use App\Jobs\Monitoring\CheckPipelineHealthJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ──────────────────────────────────────────────────────────────────────────────
// Continuous Market-Intelligence Pipeline
// Single cron entry required on the server:
//   * * * * * cd /var/www/your-app && php artisan schedule:run >> /dev/null 2>&1
// ──────────────────────────────────────────────────────────────────────────────

// News ingestion — every 10 minutes
// Inspects all active sources and dispatches one IngestNewsSourceJob per due source.
// Queue is set in the job constructor; onQueue() is not available on scheduled jobs.
Schedule::job(new DispatchSourceIngestionJob())
    ->everyTenMinutes()
    ->withoutOverlapping(15)
    ->onOneServer()
    ->name('ingest:dispatch');

// Market price snapshots — every 15 minutes
Schedule::job(new CaptureMarketPricesJob())
    ->everyFifteenMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('market:capture-prices');

// Economic calendar sync — every 6 hours, 14 days ahead
Schedule::job(new SyncEconomicCalendarJob(daysAhead: 14))
    ->everySixHours()
    ->withoutOverlapping(30)
    ->onOneServer()
    ->name('calendar:sync');

// General analysis (impact scoring + classification) — hourly
Schedule::job(new AnalyzePendingStoriesJob())
    ->hourly()
    ->withoutOverlapping(45)
    ->onOneServer()
    ->name('analysis:pending-stories');

// Article generation (eligibility-gated) — every 2 hours
Schedule::job(new GenerateEligibleArticlesJob())
    ->everyTwoHours()
    ->withoutOverlapping(90)
    ->onOneServer()
    ->name('articles:generate-eligible');

// Developing-article refresh — every 30 minutes
Schedule::job(new RefreshDevelopingArticlesJob())
    ->everyThirtyMinutes()
    ->withoutOverlapping(25)
    ->onOneServer()
    ->name('articles:refresh-developing');

// Daily market briefing — 05:30 UTC (before European open)
Schedule::job(new GenerateDailyBriefingJob())
    ->dailyAt('05:30')
    ->timezone('UTC')
    ->onOneServer()
    ->name('articles:daily-briefing');

// SEO maintenance (sitemap + indexing) — 02:00 UTC
Schedule::job(new MaintainSeoJob())
    ->dailyAt('02:00')
    ->timezone('UTC')
    ->onOneServer()
    ->name('seo:maintain');

// Pipeline health monitor — every 10 minutes
Schedule::job(new CheckPipelineHealthJob())
    ->everyTenMinutes()
    ->withoutOverlapping(5)
    ->onOneServer()
    ->name('monitoring:health');
