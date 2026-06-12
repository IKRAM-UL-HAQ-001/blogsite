<?php

namespace App\Jobs\Articles;

use App\Jobs\GenerateArticleJob;
use App\Models\MarketImpact;
use App\Models\PipelineRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Generates articles only for MarketImpact records that meet the eligibility
 * threshold defined in the spec (impact_level = high OR score >= 65).
 *
 * Runs every 2 hours. Processes up to 20 eligible impacts per run.
 */
final class GenerateEligibleArticlesJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 2;
    public int $timeout   = 600;
    public int $uniqueFor = 5400; // 90 minutes

    public function __construct()
    {
        $this->onQueue('generation');
    }

    public function handle(): void
    {
        $run = PipelineRun::start('generate_eligible_articles');

        $eligible = MarketImpact::where(function ($q) {
                $q->where('impact_level', 'high')
                  ->orWhere('score', '>=', 65);
            })
            ->doesntHave('article')
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get();

        $dispatched = 0;

        foreach ($eligible as $impact) {
            GenerateArticleJob::dispatch($impact);
            $dispatched++;
        }

        $run->complete($eligible->count(), $dispatched);

        Log::info("GenerateEligibleArticlesJob: dispatched {$dispatched} article generations.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateEligibleArticlesJob failed: ' . $e->getMessage());
    }
}
