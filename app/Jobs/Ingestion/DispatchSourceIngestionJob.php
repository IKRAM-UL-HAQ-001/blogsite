<?php

namespace App\Jobs\Ingestion;

use App\Models\NewsSource;
use App\Models\PipelineRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Runs every 10 minutes. Inspects every active NewsSource and dispatches
 * an IngestNewsSourceJob for each source that is due for a fetch.
 *
 * Using ShouldBeUnique with a 15-minute window prevents a flood of
 * redundant dispatches if the scheduler fires multiple times.
 */
final class DispatchSourceIngestionJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 1;
    public int $timeout   = 60;
    public int $uniqueFor = 900; // 15 minutes

    public function __construct()
    {
        $this->onQueue('ingestion');
    }

    public function handle(): void
    {
        $run = PipelineRun::start('dispatch_source_ingestion');

        $sources    = NewsSource::where('is_active', true)->get();
        $dispatched = 0;

        foreach ($sources as $source) {
            if (! $source->isDue()) {
                continue;
            }

            if ($source->isCircuitOpen()) {
                Log::warning("DispatchSourceIngestionJob: skipping source #{$source->id} ({$source->name}) — circuit open ({$source->configuration_json['consecutive_failures']} consecutive failures).");
                continue;
            }

            IngestNewsSourceJob::dispatch($source->id)->onQueue('ingestion');
            $dispatched++;
        }

        $run->complete($sources->count(), $dispatched, 0, ['dispatched' => $dispatched]);

        Log::info("DispatchSourceIngestionJob: dispatched {$dispatched} / {$sources->count()} sources.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('DispatchSourceIngestionJob failed: ' . $e->getMessage());
    }
}
