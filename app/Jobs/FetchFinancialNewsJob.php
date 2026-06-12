<?php

namespace App\Jobs;

use App\Services\IngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchFinancialNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('ingestion');
    }

    public function handle(IngestionService $ingestionService): void
    {
        Log::info("FetchFinancialNewsJob started.");

        $results = $ingestionService->ingestByType('financial');

        $totalStored = collect($results)->sum('stored_count');
        $totalDuplicates = collect($results)->sum('duplicates_skipped');
        $totalErrors = collect($results)->where('status', 'failed')->count();

        Log::info("FetchFinancialNewsJob finished.", [
            'stored' => $totalStored,
            'duplicates' => $totalDuplicates,
            'failed_sources' => $totalErrors,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("FetchFinancialNewsJob failed: " . $exception->getMessage());
    }
}
