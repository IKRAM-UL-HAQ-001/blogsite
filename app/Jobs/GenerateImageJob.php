<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\FeaturedImage;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Article $article;
    protected string $prompt;

    /**
     * Create a new job instance.
     */
    public function __construct(Article $article, string $prompt)
    {
        $this->article = $article;
        $this->prompt = $prompt;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $openAIService): void
    {
        try {
            Log::info("Generating image for Article ID: {$this->article->id}");
            $filePath = $openAIService->generateFeaturedImage($this->prompt);

            if ($filePath) {
                FeaturedImage::create([
                    'article_id' => $this->article->id,
                    'file_path' => $filePath,
                    'alt_text' => $this->article->title,
                    'generation_prompt' => $this->prompt
                ]);
                Log::info("Featured image created for Article ID: {$this->article->id} -> {$filePath}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to run GenerateImageJob: " . $e->getMessage());
        }
    }
}
