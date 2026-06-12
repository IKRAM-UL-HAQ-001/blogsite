<?php

namespace App\Jobs;

use App\Jobs\AnalyzeMarketImpactJob;
use App\Models\RawArticle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 90;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('analysis');
    }

    public function handle(): void
    {
        Log::info('AnalyzeNewsJob started.');

        RawArticle::where('status', 'pending')
            ->orderBy('published_at', 'asc')
            ->limit(50)
            ->get()
            ->each(function (RawArticle $rawArticle) {
                $rawArticle->update(['status' => 'processing']);
                AnalyzeMarketImpactJob::dispatch($rawArticle, null);
            });

        Log::info('AnalyzeNewsJob queued pending raw articles for analysis.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AnalyzeNewsJob failed: ' . $exception->getMessage());
    }
}
