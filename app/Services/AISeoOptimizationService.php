<?php

namespace App\Services;

use Illuminate\Support\Str;

class AISeoOptimizationService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function optimize(array $articleData, string $canonicalUrl = null): array
    {
        $content = [
            'title' => $articleData['title'] ?? $articleData['seo_title'] ?? '',
            'body' => $articleData['article'] ?? $articleData['body'] ?? '',
            'description_hint' => $articleData['meta_description'] ?? '',
        ];

        $seo = $this->openAIService->optimizeSeo($content, $canonicalUrl);

        return [
            'meta_title' => $seo['meta_title'] ?? $content['title'],
            'meta_description' => $seo['meta_description'] ?? ($content['description_hint'] ?: Str::limit($content['body'], 155, '')),
            'keywords' => $seo['keywords'] ?? '',
            'canonical_url' => $seo['canonical_url'] ?? $canonicalUrl ?? $this->buildCanonicalUrl($content['title']),
            'schema_markup' => $seo['schema_markup'] ?? $this->buildSchemaMarkup($content['title'], $seo['meta_description'] ?? '', $seo['canonical_url'] ?? $canonicalUrl),
        ];
    }

    protected function buildCanonicalUrl(string $title): string
    {
        return url('/articles/' . Str::slug($title ?: 'market-intelligence-report'));
    }

    protected function buildSchemaMarkup(string $title, string $description, string $canonicalUrl = null): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $title,
            'description' => Str::limit($description, 200, ''),
            'datePublished' => now()->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => config('app.name', 'Financial Intelligence'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Financial Intelligence'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url('/logo.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                'id' => $canonicalUrl ?: $this->buildCanonicalUrl($title),
            ],
        ];

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
