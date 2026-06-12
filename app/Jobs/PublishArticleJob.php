<?php

namespace App\Jobs;

use App\Models\Article;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function handle(): void
    {
        try {
            $this->article->update([
                'status' => 'published',
                'published_at' => $this->article->published_at ?? now(),
            ]);

            UpdateSitemapJob::dispatch();
            SubmitIndexingJob::dispatch(url('/articles/' . $this->article->slug));

            Log::info("Article published ID: {$this->article->id}");
        } catch (\Exception $e) {
            Log::error("Failed to run PublishArticleJob: " . $e->getMessage());
        }
    }
}
