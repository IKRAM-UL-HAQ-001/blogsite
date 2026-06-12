<?php

namespace App\Jobs;

use App\Services\IngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('ingestion');
    }

    /**
     * Execute the job.
     */
    public function handle(IngestionService $ingestionService): void
    {
        Log::info("FetchCalendarJob started.");

        $results = $ingestionService->ingestByType('economic_calendar');

        $totalStored = collect($results)->sum('stored_count');
        $totalDuplicates = collect($results)->sum('duplicates_skipped');
        $totalFetched = collect($results)->sum('fetched_count');

        Log::info("FetchCalendarJob finished.", [
            'fetched' => $totalFetched,
            'stored' => $totalStored,
            'duplicates' => $totalDuplicates,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("FetchCalendarJob failed: " . $exception->getMessage());
    }
}
