<?php

namespace App\Jobs;

use App\Services\IndexingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitIndexingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $url;
    protected string $type;

    /**
     * Create a new job instance.
     */
    public function __construct(string $url, string $type = 'URL_UPDATED')
    {
        $this->url = $url;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(IndexingService $indexingService): void
    {
        Log::info("Submitting URL to Google & Bing indexing: {$this->url}");

        $googleSuccess = $indexingService->submitToGoogle($this->url, $this->type);
        $bingSuccess = $indexingService->submitToBing($this->url);

        Log::info("SubmitIndexingJob complete. Google: " . ($googleSuccess ? 'SUCCESS' : 'FAILED') . 
                 " | Bing: " . ($bingSuccess ? 'SUCCESS' : 'FAILED'));
    }
}
