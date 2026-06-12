<?php

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Article $article;

    public int $tries = 2;
    public int $backoff = 60;
    public int $timeout = 180;

    public function __construct(Article $article)
    {
        $this->article = $article;
        $this->onQueue('publish');
    }

    public function handle(): void
    {
        try {
            Log::info("PublishPostJob started for Article ID: {$this->article->id}");

            $canonicalUrl = $this->article->canonical_url ?: url('/articles/' . $this->article->slug);

            $this->article->update([
                'status' => 'published',
                'published_at' => $this->article->published_at ?? now(),
            ]);

            UpdateSitemapJob::dispatch();
            SubmitIndexingJob::dispatch($canonicalUrl);
            SubmitIndexingJob::dispatch(url('/sitemap.xml'));

            Log::info("PublishPostJob completed for Article ID: {$this->article->id}");
        } catch (\Throwable $exception) {
            Log::error('PublishPostJob failed: ' . $exception->getMessage());
        }
    }
}
