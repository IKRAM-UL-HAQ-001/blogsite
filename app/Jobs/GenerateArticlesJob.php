<?php

namespace App\Jobs;

use App\Models\MarketImpact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateArticlesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('generation');
    }

    public function handle(): void
    {
        Log::info('GenerateArticlesJob started.');

        MarketImpact::where('impact_level', 'high')
            ->doesntHave('article')
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get()
            ->each(function (MarketImpact $impact) {
                GenerateArticleJob::dispatch($impact);
            });

        Log::info('GenerateArticlesJob queued article generation for high-impact records.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateArticlesJob failed: ' . $exception->getMessage());
    }
}
