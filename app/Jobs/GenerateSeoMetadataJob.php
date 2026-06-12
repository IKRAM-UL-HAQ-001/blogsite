<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\AISeoOptimizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSeoMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Article $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    public function handle(AISeoOptimizationService $seoService): void
    {
        try {
            $seo = $seoService->optimize([
                'title' => $this->article->title,
                'article' => $this->article->body,
                'meta_description' => $this->article->seo_description,
            ], $this->article->canonical_url ?? url('/articles/' . $this->article->slug));

            $this->article->update([
                'seo_title' => $seo['meta_title'] ?? $this->article->title,
                'seo_description' => $seo['meta_description'] ?? $this->article->seo_description,
                'focus_keywords' => $seo['keywords'] ?? $this->article->focus_keywords,
                'canonical_url' => $seo['canonical_url'] ?? $this->article->canonical_url,
                'schema_markup' => $seo['schema_markup'] ?? $this->article->schema_markup,
            ]);

            Log::info("SEO metadata generated for Article ID: {$this->article->id}");
        } catch (\Exception $e) {
            Log::error("Failed to run GenerateSeoMetadataJob: " . $e->getMessage());
        }
    }
}
