<?php

namespace App\Jobs\Analysis;

use App\Jobs\AnalyzeMarketImpactJob;
use App\Models\PipelineRun;
use App\Models\RawArticle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Picks up pending RawArticles and dispatches per-article impact analysis jobs.
 * Runs hourly. Processes up to 50 articles per run to avoid runaway batches.
 */
final class AnalyzePendingStoriesJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 3;
    public int $timeout   = 300;
    public int $uniqueFor = 2700; // 45 minutes

    public function __construct()
    {
        $this->onQueue('analysis');
    }

    public function handle(): void
    {
        $run = PipelineRun::start('analyze_pending_stories');

        $pending = RawArticle::where('status', 'pending')
            ->orderBy('published_at', 'asc')
            ->limit(50)
            ->get();

        $dispatched = 0;

        foreach ($pending as $rawArticle) {
            $rawArticle->update(['status' => 'processing']);
            AnalyzeMarketImpactJob::dispatch($rawArticle, null);
            $dispatched++;
        }

        $run->complete($pending->count(), $dispatched);

        Log::info("AnalyzePendingStoriesJob: dispatched {$dispatched} articles for impact analysis.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('AnalyzePendingStoriesJob failed: ' . $e->getMessage());
    }
}
