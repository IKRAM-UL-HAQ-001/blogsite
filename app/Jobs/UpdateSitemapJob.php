<?php

namespace App\Jobs;

use App\Jobs\SubmitIndexingJob;
use App\Services\SitemapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateSitemapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SitemapService $sitemapService): void
    {
        if ($sitemapService->generate()) {
            SubmitIndexingJob::dispatch(url('/sitemap.xml'));
            Log::info('Sitemap update complete and indexing notification queued.');
        } else {
            Log::warning('Sitemap update failed.');
        }
    }
}
