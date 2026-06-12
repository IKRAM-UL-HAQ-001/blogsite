<?php

namespace App\Services;

use Illuminate\Support\Str;

class MarketArticleGenerationService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function generate(array $economicEvents, array $newsItems, array $marketData): array
    {
        $economicEvents = $this->normalizeTextList($economicEvents);
        $newsItems = $this->normalizeTextList($newsItems);
        $marketData = $this->normalizeMarketData($marketData);

        $article = $this->openAIService->generateMarketArticle($economicEvents, $newsItems, $marketData);

        return $this->hydrateResult($article, $economicEvents, $newsItems, $marketData);
    }

    protected function normalizeTextList(array $items): array
    {
        return array_values(array_filter(array_map(function ($item) {
            if (is_string($item)) {
                return trim($item);
            }
            if (is_array($item)) {
                return trim(implode(' ', $item));
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

    protected function hydrateResult(array $article, array $economicEvents, array $newsItems, array $marketData): array
    {
        $title = $article['title'] ?? ($article['seo_title'] ?? $this->buildFallbackTitle($economicEvents, $newsItems));
        $slug = $article['slug'] ?? Str::slug($title);

        if (empty($slug)) {
            $slug = Str::slug($article['seo_title'] ?? $title ?: 'market-article');
        }

        return [
            'title' => $title,
            'seo_title' => $article['seo_title'] ?? $title,
            'meta_description' => $article['meta_description'] ?? $this->buildFallbackDescription($economicEvents, $newsItems),
            'slug' => $slug,
            'article' => $article['article'] ?? $article['body'] ?? $this->buildFallbackArticle($economicEvents, $newsItems, $marketData),
            'faqs' => $article['faqs'] ?? $this->buildFallbackFaqs($economicEvents, $newsItems),
            'trading_outlook' => $article['trading_outlook'] ?? $this->buildFallbackOutlook($economicEvents, $newsItems, $marketData),
            'source_inputs' => [
                'economic_events' => $economicEvents,
                'financial_news' => $newsItems,
                'market_data' => $marketData,
            ],
        ];
    }

    protected function buildFallbackTitle(array $economicEvents, array $newsItems): string
    {
        $headline = $economicEvents[0] ?? $newsItems[0] ?? 'Market update';
        return Str::limit("Market Intelligence: {$headline}", 70, '');
    }

    protected function buildFallbackDescription(array $economicEvents, array $newsItems): string
    {
        $summary = $economicEvents[0] ?? $newsItems[0] ?? 'A concise market analysis report based on the latest economic, market, and news data.';
        return Str::limit("Deep dive into the latest market-moving developments and actionable trading outlook for the assets affected by {$summary}.", 155, '');
    }

    protected function buildFallbackArticle(array $economicEvents, array $newsItems, array $marketData): string
    {
        $article = "## Market Intelligence Brief\n\n";
        $article .= "### Economic Events\n";
        foreach ($economicEvents as $event) {
            $article .= "- {$event}\n";
        }
        $article .= "\n### Financial News\n";
        foreach ($newsItems as $news) {
            $article .= "- {$news}\n";
        }
        $article .= "\n### Market Data\n";
        foreach ($marketData as $row) {
            $article .= "- " . ($row['symbol'] ?? 'unknown') . ": price=" . ($row['price'] ?? 'n/a') . ", change=" . ($row['change'] ?? 'n/a') . ", volume=" . ($row['volume'] ?? 'n/a') . "\n";
        }
        $article .= "\n### Trading Outlook\n";
        $article .= "Expect heightened volatility and a cautious bias until key macro releases confirm the current trend. Focus on risk management and asset selection around the strongest economic signals.\n";

        return $article;
    }

    protected function buildFallbackFaqs(array $economicEvents, array $newsItems): array
    {
        return [
            [
                'question' => 'What is driving the market outlook?',
                'answer' => 'The current outlook is driven by the latest economic events, news sentiment, and market data, especially the highest-impact releases and moving assets.',
            ],
            [
                'question' => 'Which indicators should traders watch?',
                'answer' => 'Traders should monitor key economic releases, headline news developments, and price action in major assets to confirm trend direction.',
            ],
            [
                'question' => 'How should risk be managed?',
                'answer' => 'Maintain strict position sizing, use stop-loss levels, and avoid overexposure until market direction is validated by follow-on data.',
            ],
        ];
    }

    protected function buildFallbackOutlook(array $economicEvents, array $newsItems, array $marketData): string
    {
        return "The next trading window should remain cautious, with an emphasis on macro-driven cross-asset correlations. Prioritize liquid instruments, watch for event-related spikes, and avoid chasing volatile moves without confirmation.";
    }
}
