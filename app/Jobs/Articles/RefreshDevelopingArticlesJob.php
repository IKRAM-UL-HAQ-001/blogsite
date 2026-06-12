<?php

namespace App\Jobs\Articles;

use App\Models\Article;
use App\Models\PipelineRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Finds recently published articles that may have stale facts and flags them
 * for re-evaluation. Runs every 30 minutes.
 *
 * Phase 1: identifies candidates only — full content refresh is Phase 3.
 */
final class RefreshDevelopingArticlesJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 2;
    public int $timeout   = 300;
    public int $uniqueFor = 1500; // 25 minutes

    public function __construct()
    {
        $this->onQueue('generation');
    }

    public function handle(): void
    {
        $run = PipelineRun::start('refresh_developing_articles');

        // Articles published in the last 6 hours that haven't been touched in 30+ minutes
        $developing = Article::where('status', 'published')
            ->where('published_at', '>=', now()->subHours(6))
            ->where('updated_at', '<=', now()->subMinutes(30))
            ->orderBy('updated_at', 'asc')
            ->limit(10)
            ->get();

        // Phase 3 will dispatch targeted re-generation jobs here.
        // For now we log the candidates so the admin dashboard can surface them.

        $run->complete($developing->count(), 0, 0, [
            'candidate_ids' => $developing->pluck('id')->all(),
            'note'          => 'content refresh dispatching arrives in Phase 3',
        ]);

        Log::info("RefreshDevelopingArticlesJob: found {$developing->count()} developing articles.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RefreshDevelopingArticlesJob failed: ' . $e->getMessage());
    }
}
