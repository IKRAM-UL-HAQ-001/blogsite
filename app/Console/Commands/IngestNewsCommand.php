<?php

namespace App\Console\Commands;

use App\Services\IngestionService;
use App\Jobs\FetchNewsJob;
use App\Jobs\FetchCalendarJob;
use App\Jobs\FetchFinancialNewsJob;
use App\Jobs\FetchGeopoliticalNewsJob;
use App\Jobs\FetchMarketNewsJob;
use Illuminate\Console\Command;

class IngestNewsCommand extends Command
{
    protected $signature = 'ingest:news
                            {--type=all : Source type to ingest (all, financial, geopolitical, market, economic_calendar)}
                            {--queue : Dispatch to queue instead of running synchronously}
                            {--source= : Ingest a specific source by ID}';

    protected $description = 'Ingest news from configured sources (financial, geopolitical, market, economic calendar)';

    public function handle(IngestionService $ingestionService): int
    {
        $type = $this->option('type');
        $useQueue = $this->option('queue');
        $sourceId = $this->option('source');

        // If a specific source ID is provided
        if ($sourceId) {
            return $this->ingestSpecificSource($ingestionService, (int) $sourceId, $useQueue);
        }

        $validTypes = ['all', 'financial', 'geopolitical', 'market', 'economic_calendar'];

        if (!in_array($type, $validTypes)) {
            $this->error("Invalid type '{$type}'. Valid options: " . implode(', ', $validTypes));
            return self::FAILURE;
        }

        $this->info("Starting ingestion for type: {$type}");

        if ($useQueue) {
            return $this->dispatchToQueue($type);
        }

        return $this->runSynchronous($ingestionService, $type);
    }

    protected function ingestSpecificSource(IngestionService $ingestionService, int $sourceId, bool $useQueue): int
    {
        $source = \App\Models\NewsSource::find($sourceId);

        if (!$source) {
            $this->error("NewsSource with ID {$sourceId} not found.");
            return self::FAILURE;
        }

        $this->info("Ingesting source: {$source->name} (type: {$source->type})");

        $log = $ingestionService->ingestSource($source);

        $this->displayLogResult($log);

        return $log->status === 'failed' ? self::FAILURE : self::SUCCESS;
    }

    protected function dispatchToQueue(string $type): int
    {
        $jobs = match ($type) {
            'financial' => [new FetchFinancialNewsJob()],
            'geopolitical' => [new FetchGeopoliticalNewsJob()],
            'market' => [new FetchMarketNewsJob()],
            'economic_calendar' => [new FetchCalendarJob()],
            'all' => [
                new FetchFinancialNewsJob(),
                new FetchGeopoliticalNewsJob(),
                new FetchMarketNewsJob(),
                new FetchCalendarJob(),
            ],
        };

        foreach ($jobs as $job) {
            dispatch($job);
            $jobClass = get_class($job);
            $this->info("Dispatched: {$jobClass}");
        }

        $this->info('Jobs dispatched to queue. Run `php artisan queue:work --queue=ingestion` to process.');

        return self::SUCCESS;
    }

    protected function runSynchronous(IngestionService $ingestionService, string $type): int
    {
        $results = match ($type) {
            'all' => $ingestionService->ingestAll(),
            'financial', 'geopolitical', 'market', 'economic_calendar' => $ingestionService->ingestByType($type),
        };

        $this->info('');
        $this->info('=== Ingestion Results ===');

        $totalFetched = 0;
        $totalStored = 0;
        $totalDuplicates = 0;
        $totalErrors = 0;
        $hasFailure = false;

        foreach ($results as $log) {
            $this->displayLogResult($log);

            $totalFetched += $log->fetched_count;
            $totalStored += $log->stored_count;
            $totalDuplicates += $log->duplicates_skipped;
            $totalErrors += $log->error_count;

            if ($log->status === 'failed') {
                $hasFailure = true;
            }
        }

        $this->newLine();
        $this->info("Total: Fetched={$totalFetched} | Stored={$totalStored} | Duplicates={$totalDuplicates} | Errors={$totalErrors}");

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }

    protected function displayLogResult($log): void
    {
        $statusIcon = match ($log->status) {
            'completed' => '<info>✓</info>',
            'partial' => '<comment>⚠</comment>',
            'failed' => '<error>✗</error>',
            default => '<comment>…</comment>',
        };

        $sourceName = $log->metadata['source_name'] ?? "Source #{$log->news_source_id}";
        $duration = $log->metadata['duration_ms'] ?? '?';

        $this->line("  {$statusIcon} {$sourceName} ({$log->source_type}): " .
            "fetched={$log->fetched_count} " .
            "stored={$log->stored_count} " .
            "dupes={$log->duplicates_skipped} " .
            "errors={$log->error_count} " .
            "[{$duration}ms]");

        if ($log->error_message) {
            $this->line("    └ Error: " . Str::limit($log->error_message, 120));
        }
    }
}
