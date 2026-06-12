<?php

namespace App\Jobs;

use App\Services\IngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 180;

    public function __construct()
    {
        $this->onQueue('ingestion');
    }

    /**
     * Execute the job.
     */
    public function handle(IngestionService $ingestionService): void
    {
        Log::info("FetchNewsJob started.");

        $results = $ingestionService->ingestAll();

        $totalStored = collect($results)->sum('stored_count');
        $totalDuplicates = collect($results)->sum('duplicates_skipped');
        $totalFetched = collect($results)->sum('fetched_count');
        $failedSources = collect($results)->where('status', 'failed')->count();

        Log::info("FetchNewsJob finished.", [
            'fetched' => $totalFetched,
            'stored' => $totalStored,
            'duplicates' => $totalDuplicates,
            'failed_sources' => $failedSources,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("FetchNewsJob failed: " . $exception->getMessage());
    }
}
