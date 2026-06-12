<?php

namespace App\Jobs;

use App\Jobs\GenerateImageJob;
use App\Jobs\GenerateSeoJob;
use App\Jobs\PublishPostJob;
use App\Models\MarketImpact;
use App\Models\Article;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GenerateArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected MarketImpact $marketImpact;

    /**
     * Create a new job instance.
     */
    public function __construct(MarketImpact $marketImpact)
    {
        $this->marketImpact = $marketImpact;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $openAIService): void
    {
        $rawTitle = '';
        $rawBody = '';

        if ($this->marketImpact->rawArticle) {
            $rawTitle = $this->marketImpact->rawArticle->title;
            $rawBody = $this->marketImpact->rawArticle->body;
        } elseif ($this->marketImpact->economicEvent) {
            $rawTitle = $this->marketImpact->economicEvent->event_name;
            $rawBody = "Calendar Event impact details: Score " . $this->marketImpact->score . 
                       ", country " . $this->marketImpact->economicEvent->country;
        }

        try {
            $articleData = $openAIService->generateArticle(
                $this->marketImpact->toArray(),
                $rawTitle,
                $rawBody
            );

            $slug = Str::slug($articleData['title']);
            
            $originalSlug = $slug;
            $count = 1;
            while (Article::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            $article = Article::create([
                'market_impact_id' => $this->marketImpact->id,
                'title' => $articleData['title'] ?? 'Market Intelligence Report',
                'slug' => $slug,
                'body' => $articleData['body'] ?? '',
                'excerpt' => $articleData['excerpt'] ?? Str::limit($articleData['body'] ?? '', 200, ''),
                'seo_title' => $articleData['seo_title'] ?? $articleData['title'] ?? 'Market Intelligence Report',
                'seo_description' => $articleData['seo_description'] ?? Str::limit($articleData['body'] ?? '', 155, ''),
                'focus_keywords' => $articleData['focus_keywords'] ?? '',
                'status' => 'draft',
                'published_at' => null,
            ]);

            Log::info("Article generated as draft ID: {$article->id}. Slug: {$slug}");

            $dallePrompt = $articleData['dalle_prompt'] ?? "A futuristic global financial hub dark vector graphics";
            Bus::chain([
                new GenerateSeoJob($article),
                new GenerateImageJob($article, $dallePrompt),
                new PublishPostJob($article),
            ])->dispatch();

        } catch (\Exception $e) {
            Log::error("Failed to run GenerateArticleJob: " . $e->getMessage());
        }
    }
}
