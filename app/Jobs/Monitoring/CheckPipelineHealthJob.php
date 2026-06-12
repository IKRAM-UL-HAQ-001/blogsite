<?php

namespace App\Jobs\Monitoring;

use App\Models\MarketAssetPrice;
use App\Models\PipelineRun;
use App\Models\RawArticle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

/**
 * Runs every 10 minutes. Checks for signs of pipeline stall and logs
 * structured warnings so the admin dashboard can surface them.
 *
 * Phase 4 will add Telegram / email alert dispatch here.
 */
final class CheckPipelineHealthJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 1;
    public int $timeout   = 60;
    public int $uniqueFor = 300; // 5 minutes

    public function __construct()
    {
        $this->onQueue('critical');
    }

    public function handle(): void
    {
        $alerts = [];

        // 1. No raw articles ingested in last 30 minutes
        $lastArticle = RawArticle::orderByDesc('fetched_at')->value('fetched_at');
        if (! $lastArticle || now()->diffInMinutes($lastArticle) > 30) {
            $alerts[] = [
                'level'   => 'critical',
                'code'    => 'INGESTION_STALLED',
                'message' => 'No articles have been ingested in the last 30 minutes.',
                'last_at' => $lastArticle,
            ];
        }

        // 2. No market prices captured in last 30 minutes
        $lastPrice = MarketAssetPrice::orderByDesc('recorded_at')->value('recorded_at');
        if (! $lastPrice || now()->diffInMinutes($lastPrice) > 30) {
            $alerts[] = [
                'level'   => 'critical',
                'code'    => 'MARKET_DATA_STALLED',
                'message' => 'No market prices have been recorded in the last 30 minutes.',
                'last_at' => $lastPrice,
            ];
        }

        // 3. High failure rate in recent pipeline runs (last 1 hour)
        $recentRuns   = PipelineRun::recent(1)->count();
        $failedRuns   = PipelineRun::recent(1)->failed()->count();
        if ($recentRuns > 0 && ($failedRuns / $recentRuns) > 0.2) {
            $alerts[] = [
                'level'        => 'warning',
                'code'         => 'HIGH_FAILURE_RATE',
                'message'      => "Pipeline failure rate is above 20% ({$failedRuns}/{$recentRuns} runs in last hour).",
                'failed_runs'  => $failedRuns,
                'total_runs'   => $recentRuns,
            ];
        }

        // 4. Large number of pending articles (analysis backlog)
        $pendingCount = RawArticle::where('status', 'pending')->count();
        if ($pendingCount > 500) {
            $alerts[] = [
                'level'   => 'warning',
                'code'    => 'ANALYSIS_BACKLOG',
                'message' => "Analysis backlog is large: {$pendingCount} pending articles.",
                'count'   => $pendingCount,
            ];
        }

        if (empty($alerts)) {
            Log::debug('CheckPipelineHealthJob: all systems nominal.');
            return;
        }

        foreach ($alerts as $alert) {
            $level = $alert['level'];
            Log::$level('CheckPipelineHealthJob alert', $alert);
        }

        // Store a pipeline_runs record so the dashboard can surface recent alerts
        PipelineRun::start('pipeline_health_check')
            ->complete(count($alerts), 0, count($alerts), ['alerts' => $alerts]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CheckPipelineHealthJob failed: ' . $e->getMessage());
    }
}
