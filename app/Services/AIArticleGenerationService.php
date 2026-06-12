<?php

namespace App\Services;

use Illuminate\Support\Str;

class AIArticleGenerationService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function generate(array $economicEvents, array $newsItems, array $marketData): array
    {
        $economic = $this->normalizeTextList($economicEvents);
        $news = $this->normalizeTextList($newsItems);
        $market = $this->normalizeMarketData($marketData);

        $result = $this->openAIService->generateMarketArticle($economic, $news, $market);

        return $this->hydrateOutput($result, $economic, $news, $market);
    }

    protected function hydrateOutput(array $article, array $economicEvents, array $newsItems, array $marketData): array
    {
        $seoTitle = $article['seo_title'] ?? $article['title'] ?? $this->buildFallbackTitle($economicEvents, $newsItems);
        $slug = $article['slug'] ?? Str::slug($seoTitle);

        if (empty($slug)) {
            $slug = Str::slug($article['title'] ?? 'market-intelligence-report');
        }

        return [
            'seo_title' => $seoTitle,
            'meta_description' => $article['meta_description'] ?? $this->buildFallbackDescription($economicEvents, $newsItems),
            'slug' => $slug,
            'article' => $article['article'] ?? $article['body'] ?? $this->buildFallbackArticle($economicEvents, $newsItems, $marketData),
            'faqs' => $article['faqs'] ?? $this->buildFallbackFaqs($economicEvents, $newsItems),
            'trading_outlook' => $article['trading_outlook'] ?? $this->buildFallbackOutlook($economicEvents, $newsItems, $marketData),
        ];
    }

    protected function normalizeTextList(array $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
            if (is_string($item)) {
                return trim($item);
            }

            if (is_array($item)) {
                return trim(implode(' ', array_map('strval', $item)));
            }

            return null;
        }, $items)));
    }

    protected function normalizeMarketData(array $rows): array
    {
        return array_values(array_filter(array_map(function ($row) {
            if (!is_array($row)) {
                return null;
            }

            return [
                'symbol' => $row['symbol'] ?? null,
                'price' => isset($row['price']) ? (float) $row['price'] : null,
                'change' => isset($row['change']) ? (float) $row['change'] : null,
                'volume' => isset($row['volume']) ? (float) $row['volume'] : null,
                'timestamp' => $row['timestamp'] ?? null,
            ];
        }, $rows)));
    }

    protected function buildFallbackTitle(array $economicEvents, array $newsItems): string
    {
        $headline = $economicEvents[0] ?? $newsItems[0] ?? 'Market Intelligence Update';

        return Str::limit("Market Intelligence: {$headline}", 60, '');
    }

    protected function buildFallbackDescription(array $economicEvents, array $newsItems): string
    {
        $summary = $economicEvents[0] ?? $newsItems[0] ?? 'latest economic and market developments';

        return Str::limit("A market intelligence report based on recent economic events, news developments, and market data. Actionable outlook for traders and investors.", 155, '');
    }

    protected function buildFallbackArticle(array $economicEvents, array $newsItems, array $marketData): string
    {
        $article = "## Market Intelligence Report\n\n";
        $article .= "### Economic Events\n";

        foreach ($economicEvents as $event) {
            $article .= "- {$event}\n";
        }

        $article .= "\n### News\n";
        foreach ($newsItems as $news) {
            $article .= "- {$news}\n";
        }

        $article .= "\n### Market Data\n";
        foreach ($marketData as $row) {
            $article .= "- " . ($row['symbol'] ?? 'unknown') . ": price=" . ($row['price'] ?? 'n/a') . ", change=" . ($row['change'] ?? 'n/a') . ", volume=" . ($row['volume'] ?? 'n/a') . "\n";
        }

        $article .= "\n### Trading Outlook\n";
        $article .= "Expect a cautious bias into the next session while remaining attentive to key macro releases and headline risk. Focus on liquidity and disciplined risk management.\n";

        return $article;
    }

    protected function buildFallbackFaqs(array $economicEvents, array $newsItems): array
    {
        return [
            [
                'question' => 'What is driving the current market outlook?',
                'answer' => 'The outlook is shaped by the latest economic event releases, news sentiment, and market price action across major assets.',
            ],
            [
                'question' => 'Which data points should traders watch?',
                'answer' => 'Traders should monitor key economic releases, headline news developments, and the strongest moves in core market instruments.',
            ],
            [
                'question' => 'How should risk be managed?',
                'answer' => 'Maintain disciplined sizing, use stops around vulnerability points, and avoid adding direction until follow-through is confirmed.',
            ],
        ];
    }

    protected function buildFallbackOutlook(array $economicEvents, array $newsItems, array $marketData): string
    {
        return 'Expect measured volatility and a cautious trading bias as markets digest economic news and headline developments. Prioritize risk management and wait for clearer directional conviction before adding exposure.';
    }
}
