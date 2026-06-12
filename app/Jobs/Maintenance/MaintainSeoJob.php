<?php

namespace App\Jobs\Maintenance;

use App\Jobs\SubmitIndexingJob;
use App\Models\PipelineRun;
use App\Services\SitemapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Daily SEO maintenance: regenerates the sitemap and submits fresh
 * article URLs to Google and Bing indexing APIs.
 * Runs at 02:00 UTC when traffic is lowest.
 */
final class MaintainSeoJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 2;
    public int $timeout   = 300;
    public int $uniqueFor = 82800; // 23 hours

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(SitemapService $sitemapService): void
    {
        $run = PipelineRun::start('maintain_seo');

        try {
            if ($sitemapService->generate()) {
                SubmitIndexingJob::dispatch(url('/sitemap.xml'))->onQueue('maintenance');
                Log::info('MaintainSeoJob: sitemap regenerated and indexing submission queued.');
            } else {
                Log::warning('MaintainSeoJob: sitemap generation returned false.');
            }

            $run->complete(1, 1);
        } catch (\Throwable $e) {
            $run->fail($e->getMessage());
            Log::error('MaintainSeoJob failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('MaintainSeoJob permanently failed: ' . $e->getMessage());
    }
}
