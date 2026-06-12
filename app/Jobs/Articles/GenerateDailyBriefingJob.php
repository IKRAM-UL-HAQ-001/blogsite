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
 * Publishes a complete daily forex and geopolitical market briefing.
 * Runs once daily at 05:30 UTC so it is ready before European markets open.
 *
 * Selects the highest-scored impact from the previous 24 hours as the
 * briefing anchor. Phase 3 will introduce a dedicated briefing template.
 */
final class GenerateDailyBriefingJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 2;
    public int $timeout   = 600;
    public int $uniqueFor = 82800; // 23 hours

    public function __construct()
    {
        $this->onQueue('generation');
    }

    public function handle(): void
    {
        $run = PipelineRun::start('generate_daily_briefing');

        $topImpacts = MarketImpact::where('created_at', '>=', now()->subHours(24))
            ->orderByDesc('score')
            ->with(['rawArticle', 'economicEvent', 'geopoliticalEvent'])
            ->limit(10)
            ->get();

        if ($topImpacts->isEmpty()) {
            $run->complete(0, 0, 0, ['note' => 'no impacts in last 24h — briefing skipped']);
            Log::info('GenerateDailyBriefingJob: no impacts found, briefing skipped.');
            return;
        }

        // Use the highest-scored impact as the anchor for today's briefing.
        // Phase 3 will replace this with a dedicated multi-source briefing prompt.
        $anchor = $topImpacts->first();

        GenerateArticleJob::dispatch($anchor);

        $run->complete($topImpacts->count(), 1, 0, [
            'anchor_impact_id' => $anchor->id,
            'top_score'        => $anchor->score,
        ]);

        Log::info("GenerateDailyBriefingJob: dispatched briefing generation (anchor impact #{$anchor->id}).");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateDailyBriefingJob failed: ' . $e->getMessage());
    }
}
