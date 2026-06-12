<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIService
{
    protected ?string $apiKey;
    protected string $model;
    protected string $writingModel;

    public function __construct()
    {
        $this->apiKey = config('ai.openai_api_key');
        $this->model = config('ai.openai_model', 'gpt-4o-mini');
        $this->writingModel = config('ai.openai_writing_model', 'gpt-4o-mini');
    }

    /**
     * Determine if we should run in Mock mode.
     */
    protected function isMockMode(): bool
    {
        return empty($this->apiKey) || str_contains($this->apiKey, 'your-openai');
    }

    /**
     * Analyze market sentiment of news text or economic event.
     */
    public function analyzeSentiment(string $text, string $contextType = 'news'): array
    {
        if ($this->isMockMode()) {
            return $this->getMockSentiment($text, $contextType);
        }

        try {
            $client = OpenAI::client($this->apiKey);
            
            $prompt = "You are a senior financial analyst and quantitative risk officer. Analyze the following {$contextType} item for market impact.
            You must return a JSON object with the following keys:
            - 'sentiment': Must be one of 'bullish', 'bearish', 'neutral'.
            - 'score': Numeric sentiment score from -100 (extremely bearish) to +100 (extremely bullish).
            - 'impact_level': Must be one of 'low', 'medium', 'high'.
            - 'affected_assets': An array of affected assets, symbols or currency pairs (e.g., ['EURUSD', 'XAUUSD', 'SPX', 'USOUSD']). Limit to max 4 assets.
            - 'market_summary': A concise 2-3 sentence technical explanation of why this has this impact.

            Text to analyze:
            \"{$text}\"";

            $response = $client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You always output strictly valid JSON.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->choices[0]->message->content;
            return json_decode($content, true) ?? $this->getMockSentiment($text, $contextType);
        } catch (\Exception $e) {
            Log::error("OpenAI Sentiment Analysis Error: " . $e->getMessage());
            return $this->getMockSentiment($text, $contextType);
        }
    }

    /**
     * Analyze a geopolitical event for market impact and risk assessment.
     */
    public function analyzeGeopoliticalEvent(string $context): array
    {
        if ($this->isMockMode()) {
            return $this->getMockGeopoliticalAnalysis($context);
        }

        try {
            $client = OpenAI::client($this->apiKey);

            $prompt = "You are a senior geopolitical risk analyst and financial intelligence officer. Analyze the following geopolitical event for its market impact and risk implications.

            You must return a JSON object with the following keys:
            - 'sentiment': Must be one of 'bullish', 'bearish', 'neutral'.
            - 'sentiment_score': Numeric score from -100 (extremely bearish) to +100 (extremely bullish).
            - 'confidence_score': Your confidence in this analysis from 0 to 100.
            - 'impact_level': Must be one of 'low', 'medium', 'high'.
            - 'affected_assets': An array of affected assets, symbols or currency pairs (e.g., ['EURUSD', 'XAUUSD', 'SPX', 'USOUSD', 'BTCUSD']). Limit to max 6 assets.
            - 'market_summary': A concise 2-3 sentence explanation of the direct market impact.
            - 'risk_factors': An array of 3-5 specific risk factors identified (strings).
            - 'geopolitical_analysis': A detailed 3-5 paragraph analysis covering the strategic implications, likely responses from key actors, and potential escalation scenarios.
            - 'timeline_projection': An object with keys 'short_term' (0-2 weeks), 'medium_term' (1-3 months), 'long_term' (3-12 months), each containing a 1-2 sentence forecast.
            - 'historical_parallels': An array of 1-3 objects with keys 'event' (name of historical event), 'year' (year it occurred), 'outcome' (1 sentence summary of market impact).

            Event to analyze:
            \"{$context}\"";

            $response = $client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You always output strictly valid JSON. You are an expert in geopolitical risk analysis and financial market impact assessment.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->choices[0]->message->content;
            return json_decode($content, true) ?? $this->getMockGeopoliticalAnalysis($context);
        } catch (\Exception $e) {
            Log::error("OpenAI Geopolitical Analysis Error: " . $e->getMessage());
            return $this->getMockGeopoliticalAnalysis($context);
        }
    }

    /**
     * Generate an SEO optimized financial article based on impact data.
     */
    public function generateArticle(array $impactData, string $rawTitle, string $rawBody): array
    {
        if ($this->isMockMode()) {
            return $this->getMockArticle($impactData, $rawTitle);
        }

        try {
            $client = OpenAI::client($this->apiKey);

            $prompt = "You are a professional financial journalist writing for Forex Traders, Investors, and Analysts.
            Write a detailed, premium financial intelligence article based on this market impact data:
            - Sentiment: {$impactData['sentiment']}
            - Sentiment Score: {$impactData['score']}
            - Impact Level: {$impactData['impact_level']}
            - Affected Assets: " . implode(', ', $impactData['affected_assets']) . "
            - Analysis: {$impactData['market_summary']}

            Original Event/News Title: {$rawTitle}
            Original News Content/Data: {$rawBody}

            Requirements:
            1. Write a complete, comprehensive, and original article (400-600 words).
            2. Divide into clean sections with H2 and H3 markdown tags (e.g., Introduction, Deep Dive, Market Impact, Forward Outlook).
            3. Do not copy the original text; synthesize it and add technical depth.
            4. Optimize for SEO. Focus on keywords relevant to the affected assets.
            5. Return a JSON object with the following keys:
               - 'title': A compelling, click-worthy article title.
               - 'body': The markdown-formatted body of the article.
               - 'excerpt': A short 1-2 sentence preview.
               - 'seo_title': A meta title (max 60 chars) including primary keyword.
               - 'seo_description': A meta description (max 155 chars) summarizing the value.
               - 'focus_keywords': A comma-separated list of focus keywords (e.g., 'EURUSD, Inflation, Federal Reserve').
               - 'dalle_prompt': A detailed prompt for DALL-E 3 to generate a relevant featured image. The image should be conceptual, modern, financial, and not contain text.";

            $response = $client->chat()->create([
                'model' => $this->writingModel,
                'messages' => [
                    ['role' => 'system', 'content' => 'You always output strictly valid JSON.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'response_format' => ['type' => 'json_object']
            ]);

            $content = $response->choices[0]->message->content;
            return json_decode($content, true) ?? $this->getMockArticle($impactData, $rawTitle);
        } catch (\Exception $e) {
            Log::error("OpenAI Article Generation Error: " . $e->getMessage());
            return $this->getMockArticle($impactData, $rawTitle);
        }
    }

    /**
     * Generate a market-focused AI article based on economic events, news, and market data.
     */
    public function generateMarketArticle(array $economicEvents, array $financialNews, array $marketData): array
    {
        if ($this->isMockMode()) {
            return $this->getMockMarketArticle($economicEvents, $financialNews, $marketData);
        }

        try {
            $client = OpenAI::client($this->apiKey);
            $eventsText = implode("\n", array_map(fn($item) => is_array($item) ? implode(' ', $item) : (string) $item, $economicEvents));
            $newsText = implode("\n", array_map(fn($item) => is_array($item) ? implode(' ', $item) : (string) $item, $financialNews));
            $marketText = $this->formatMarketDataForPrompt($marketData);

            $prompt = "You are a senior financial journalist writing a market intelligence report. Use the inputs below to create one comprehensive article.

Economic Events:
{$eventsText}

Financial News:
{$newsText}

Market Data:
{$marketText}

Requirements:
1. Return valid JSON only.
2. Use the exact keys: seo_title, meta_description, slug, article, faqs, trading_outlook.
3. seo_title should be an SEO-friendly title under 60 characters.
4. meta_description should be under 155 characters.
5. slug should be URL-safe and derived from the article topic.
6. article should be a full, polished report with sections and markdown formatting.
7. faqs should be an array of 3 short question/answer pairs.
8. trading_outlook should be a concise 2-3 sentence tactical outlook for traders.
";

            $response = $client->chat()->create([
                'model' => $this->writingModel,
                'messages' => [
                    ['role' => 'system', 'content' => 'You always output strictly valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content;
            $result = json_decode($content, true);

            if (!is_array($result)) {
                return $this->getMockMarketArticle($economicEvents, $financialNews, $marketData);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("OpenAI Market Article Generation Error: " . $e->getMessage());
            return $this->getMockMarketArticle($economicEvents, $financialNews, $marketData);
        }
    }

    public function optimizeSeo(array $content, string $canonicalUrl = null): array
    {
        if ($this->isMockMode()) {
            return $this->getMockSeoOptimization($content, $canonicalUrl);
        }

        try {
            $client = OpenAI::client($this->apiKey);
            $title = $content['title'] ?? '';
            $body = $content['body'] ?? $content['article'] ?? '';
            $descriptionHint = $content['description_hint'] ?? '';

            $prompt = "You are an expert SEO strategist creating search-optimized metadata for a financial market intelligence article. " .
                "Use the content to produce valid JSON with the exact keys: meta_title, meta_description, keywords, canonical_url, schema_markup. " .
                "meta_title must be 50-60 characters, meta_description 120-155 characters. keywords should be a comma-separated list of relevant SEO keywords. canonical_url should be the provided URL or a safe URL based on the content. schema_markup should be a JSON-LD string for a NewsArticle or Report that includes headline, description, datePublished, author, mainEntityOfPage, and publisher. \n\n" .
                "Title:\n{$title}\n\n" .
                "Body:\n{$body}\n\n" .
                ($descriptionHint ? "Description hint:\n{$descriptionHint}\n\n" : '') .
                ($canonicalUrl ? "Canonical URL:\n{$canonicalUrl}\n\n" : '') .
                "Return only valid JSON. Do not add any explanatory text.";

            $response = $client->chat()->create([
                'model' => $this->writingModel,
                'messages' => [
                    ['role' => 'system', 'content' => 'You always output strictly valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 400,
            ]);

            $rawResponseContent = $response->choices[0]->message->content;
            $result = json_decode($rawResponseContent, true);

            if (!is_array($result)) {
                return $this->getMockSeoOptimization($content, $canonicalUrl);
            }

            return [
                'meta_title' => $result['meta_title'] ?? $title,
                'meta_description' => $result['meta_description'] ?? $descriptionHint,
                'keywords' => $result['keywords'] ?? $this->extractKeywords($title, $body),
                'canonical_url' => $result['canonical_url'] ?? ($canonicalUrl ?: $this->buildCanonicalUrl($title)),
                'schema_markup' => $result['schema_markup'] ?? $this->buildSchemaMarkup($title, $descriptionHint, $canonicalUrl),
            ];
        } catch (\Exception $e) {
            Log::error("OpenAI SEO Optimization Error: " . $e->getMessage());
            return $this->getMockSeoOptimization($content, $canonicalUrl);
        }
    }

    protected function getMockSeoOptimization(array $content, string $canonicalUrl = null): array
    {
        $title = $content['title'] ?? 'Market Intelligence Report';
        $description = $content['description_hint'] ?? Str::limit($content['body'] ?? $content['article'] ?? '', 155, '');
        $canonical = $canonicalUrl ?: $this->buildCanonicalUrl($title);

        return [
            'meta_title' => Str::limit($title, 60, ''),
            'meta_description' => Str::limit($description, 155, ''),
            'keywords' => $this->extractKeywords($title, $content['body'] ?? $content['article'] ?? ''),
            'canonical_url' => $canonical,
            'schema_markup' => $this->buildSchemaMarkup($title, $description, $canonical),
        ];
    }

    protected function extractKeywords(string $title, string $body): string
    {
        $text = strtolower($title . ' ' . $body);
        $keywords = ['market intelligence', 'economic events', 'financial news', 'market data', 'trading outlook', 'seo'];
        $found = [];

        foreach ($keywords as $keyword) {
            if (Str::contains($text, $keyword)) {
                $found[] = $keyword;
            }
        }

        return $found ? implode(', ', array_unique($found)) : 'market intelligence, financial news, economic events';
    }

    protected function buildCanonicalUrl(string $title): string
    {
        return url('/articles/' . Str::slug($title));
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

    protected function formatMarketDataForPrompt(array $marketData): string
    {
        $lines = [];

        foreach ($marketData as $row) {
            if (!is_array($row)) {
                continue;
            }

            $symbol = $row['symbol'] ?? 'unknown';
            $price = isset($row['price']) ? $row['price'] : 'n/a';
            $change = isset($row['change']) ? $row['change'] : 'n/a';
            $volume = isset($row['volume']) ? $row['volume'] : 'n/a';
            $timestamp = $row['timestamp'] ?? 'n/a';

            $lines[] = "{$symbol} | price: {$price} | change: {$change} | volume: {$volume} | timestamp: {$timestamp}";
        }

        return implode("\n", $lines);
    }

    protected function getMockMarketArticle(array $economicEvents, array $financialNews, array $marketData): array
    {
        $title = 'Market Intelligence Report: ' . ($economicEvents[0] ?? ($financialNews[0] ?? 'Latest Market Update'));
        $seoTitle = Str::limit($title, 60);
        $slug = Str::slug($seoTitle);

        $body = "## Market Intelligence Report\n\n";
        $body .= "### Key Economic Events\n";
        foreach ($economicEvents as $event) {
            $body .= "- {$event}\n";
        }
        $body .= "\n### News Headlines\n";
        foreach ($financialNews as $news) {
            $body .= "- {$news}\n";
        }
        $body .= "\n### Market Data Snapshot\n";
        foreach ($marketData as $row) {
            $symbol = $row['symbol'] ?? 'unknown';
            $price = $row['price'] ?? 'n/a';
            $change = $row['change'] ?? 'n/a';
            $volume = $row['volume'] ?? 'n/a';
            $body .= "- {$symbol}: price={$price}, change={$change}, volume={$volume}\n";
        }
        $body .= "\n### Analysis\n";
        $body .= "The market is reacting to the latest mix of macroeconomic releases, news flow, and price action across major assets. Traders should remain tactical, focusing on confirmed momentum and risk controls.\n";

        return [
            'seo_title' => $seoTitle,
            'meta_description' => Str::limit('A tactical market intelligence article based on economic releases, news, and live market data for traders.', 155),
            'slug' => $slug,
            'article' => $body,
            'faqs' => [
                [
                    'question' => 'What is the main driver behind the market move?',
                    'answer' => 'The current market direction is primarily driven by major economic releases and headline news that change trader risk appetite.',
                ],
                [
                    'question' => 'How should traders position ahead of the next release?',
                    'answer' => 'Keep positions disciplined, use stop-loss levels, and avoid taking large directional exposure until the next data confirms the trend.',
                ],
                [
                    'question' => 'Which asset classes deserve the most attention?',
                    'answer' => 'Focus on the most liquid asset classes affected by the news, such as major FX pairs, commodities, and broad equity indices.',
                ],
            ],
            'trading_outlook' => 'Expect a cautious trading environment with moderate volatility. Maintain tight risk controls and look for clean entries on confirmed moves.',
        ];
    }

    /**
     * Generate featured image via DALL-E 3.
     */
    public function generateFeaturedImage(string $promptText): ?string
    {
        $fileName = 'featured_' . Str::random(10) . '.png';
        $directory = 'public/featured_images';

        // Ensure directory exists
        Storage::makeDirectory($directory, 0755, true);

        if ($this->isMockMode()) {
            Storage::put($directory . '/' . $fileName, 'dummy-image-content');
            return 'featured_images/' . $fileName;
        }

        try {
            $client = OpenAI::client($this->apiKey);

            $response = $client->images()->create([
                'model' => 'dall-e-3',
                'prompt' => $promptText,
                'n' => 1,
                'size' => '1024x1024',
                'response_format' => 'url',
            ]);

            $imageUrl = $response->data[0]->url;
            $httpResponse = Http::withOptions(['verify' => true])->get($imageUrl);
            $contentType = strtolower(explode(';', $httpResponse->header('Content-Type', ''))[0]);
            $imageContent = $httpResponse->body();

            if (! in_array($contentType, ['image/png', 'image/jpeg', 'image/webp'], true)) {
                throw new \RuntimeException('Unexpected image content type: ' . $contentType);
            }

            Storage::put($directory . '/' . $fileName, $imageContent);

            return 'featured_images/' . $fileName;
        } catch (\Exception $e) {
            Log::error("OpenAI Image Generation Error: " . $e->getMessage());
            Storage::put($directory . '/' . $fileName, 'placeholder-fallback');
            return 'featured_images/' . $fileName;
        }
    }

    /**
     * Generate mock sentiment data for fallback or test.
     */
    protected function getMockSentiment(string $text, string $type): array
    {
        $lower = strtolower($text);
        
        $sentiment = 'neutral';
        $score = 5;
        $impact = 'low';
        $assets = ['EURUSD', 'USDJPY'];

        if (str_contains($lower, 'cpi') || str_contains($lower, 'inflation') || str_contains($lower, 'rate')) {
            $sentiment = str_contains($lower, 'higher') || str_contains($lower, 'rise') || str_contains($lower, 'increase') ? 'bullish' : 'bearish';
            $score = $sentiment === 'bullish' ? 75 : -65;
            $impact = 'high';
            $assets = ['EURUSD', 'GBPUSD', 'XAUUSD'];
        } elseif (str_contains($lower, 'war') || str_contains($lower, 'geopolitical') || str_contains($lower, 'conflict') || str_contains($lower, 'tariff')) {
            $sentiment = 'bearish';
            $score = -80;
            $impact = 'high';
            $assets = ['XAUUSD', 'USOUSD', 'SPX'];
        } elseif (str_contains($lower, 'oil') || str_contains($lower, 'crude') || str_contains($lower, 'supply')) {
            $sentiment = str_contains($lower, 'cut') || str_contains($lower, 'tight') ? 'bullish' : 'bearish';
            $score = $sentiment === 'bullish' ? 60 : -50;
            $impact = 'medium';
            $assets = ['USOUSD', 'USDCAD'];
        }

        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'impact_level' => $impact,
            'affected_assets' => $assets,
            'market_summary' => "AI Analysis: The incoming data regarding \"{$text}\" signals changing risk parameters. The market is adjusting pricing vectors which will directly affect major assets like " . implode(', ', $assets) . " with a " . $impact . " impact level due to macroeconomic correlation."
        ];
    }

    /**
     * Generate mock article details for fallback.
     */
    protected function getMockArticle(array $impactData, string $rawTitle): array
    {
        $title = "Market Impact Analysis: " . $rawTitle;
        $assets = implode(', ', $impactData['affected_assets']);
        $sentiment = strtoupper($impactData['sentiment']);
        
        $body = "## Executive Summary\n\n";
        $body .= "A major shift is emerging across global financial markets following recent economic developments. With a calculated sentiment of **{$sentiment}** (score: {$impactData['score']}) and a **{$impactData['impact_level']}** impact index, traders are heavily adjusting their positions in key assets including **{$assets}**.\n\n";
        
        $body .= "## Technical Analysis & Correlations\n\n";
        $body .= "{$impactData['market_summary']}\n\n";
        $body .= "This event is triggering algorithmic executions across major desks. The standard deviation models indicate heightened volatility, especially for {$assets}. Support and resistance levels should be adjusted dynamically to absorb these intraday fluctuations.\n\n";
        
        $body .= "## Strategic Outlook for Traders\n\n";
        if ($impactData['sentiment'] === 'bullish') {
            $body .= "Investors should seek validation on lower timeframe pullbacks to join long trends. Key focus is on risk-on indicators and treasury yields.\n";
        } elseif ($impactData['sentiment'] === 'bearish') {
            $body .= "Traders may look to cover long portfolios or establish defensive positions. Safe-haven inflows into gold or treasury bonds may surge.\n";
        } else {
            $body .= "A range-bound wait-and-see strategy is recommended until further trend clarity emerges from secondary releases.\n";
        }

        return [
            'title' => $title,
            'body' => $body,
            'excerpt' => "The latest updates on {$rawTitle} indicate a {$impactData['sentiment']} sentiment affecting {$assets}. Read our in-depth analysis.",
            'seo_title' => Str::limit("How {$rawTitle} Affects {$assets} - Intelligence", 60),
            'seo_description' => Str::limit("Get the technical breakdown and market sentiment implications for {$assets} following {$rawTitle}. High quality insights.", 155),
            'focus_keywords' => "{$assets}, Market Impact, {$rawTitle}",
            'dalle_prompt' => "A modern dark cyber financial background with glowing neon lines tracking market charts for {$assets}, 3d render, visual art"
        ];
    }

    /**
     * Generate mock geopolitical analysis for fallback or test.
     */
    protected function getMockGeopoliticalAnalysis(string $context): array
    {
        $lower = strtolower($context);

        $sentiment = 'bearish';
        $sentimentScore = -60;
        $impact = 'high';
        $confidence = 75;
        $assets = ['XAUUSD', 'USOUSD', 'SPX'];
        $riskFactors = [
            'Escalation potential in affected region',
            'Supply chain disruption risk',
            'Safe-haven asset repositioning',
            'Currency volatility in affected economies',
        ];

        if (str_contains($lower, 'war') || str_contains($lower, 'invasion')) {
            $sentiment = 'bearish';
            $sentimentScore = -85;
            $impact = 'high';
            $assets = ['XAUUSD', 'USOUSD', 'SPX', 'EURUSD'];
            $riskFactors = ['Full-scale military conflict', 'Energy supply disruption', 'Refugee crisis and border strain', 'NATO/alliance response escalation', 'Commodity price shock'];
        } elseif (str_contains($lower, 'sanction')) {
            $sentiment = 'bearish';
            $sentimentScore = -55;
            $impact = 'medium';
            $assets = ['EURUSD', 'USDRUB', 'XAUUSD'];
            $riskFactors = ['Trade flow disruption', 'Retaliatory measures', 'Commodity supply constraints'];
        } elseif (str_contains($lower, 'trade war') || str_contains($lower, 'tariff')) {
            $sentiment = 'bearish';
            $sentimentScore = -50;
            $impact = 'medium';
            $assets = ['USDCNH', 'SPX', 'AUDUSD'];
            $riskFactors = ['Global trade contraction', 'Manufacturing supply chain disruption', 'Currency war escalation', 'Consumer price inflation'];
        } elseif (str_contains($lower, 'election') || str_contains($lower, 'coup')) {
            $sentiment = 'neutral';
            $sentimentScore = -20;
            $impact = 'medium';
            $assets = ['EURUSD', 'SPX', 'US10Y'];
            $riskFactors = ['Policy uncertainty', 'Regulatory changes', 'Alliance shifts'];
        } elseif (str_contains($lower, 'energy') || str_contains($lower, 'oil') || str_contains($lower, 'gas')) {
            $sentiment = 'bearish';
            $sentimentScore = -65;
            $impact = 'high';
            $assets = ['USOUSD', 'XNGUSD', 'EURUSD', 'CADJPY'];
            $riskFactors = ['Energy supply disruption', 'Price spike in crude/gas', 'Industrial production decline', 'Inflationary pressure'];
        } elseif (str_contains($lower, 'bank') || str_contains($lower, 'crisis') || str_contains($lower, 'bailout')) {
            $sentiment = 'bearish';
            $sentimentScore = -70;
            $impact = 'high';
            $assets = ['EURUSD', 'SPX', 'US10Y', 'XAUUSD'];
            $riskFactors = ['Systemic financial contagion', 'Credit market freeze', 'Central bank emergency intervention', 'Depositor confidence collapse'];
        }

        return [
            'sentiment' => $sentiment,
            'sentiment_score' => $sentimentScore,
            'confidence_score' => $confidence,
            'impact_level' => $impact,
            'affected_assets' => $assets,
            'market_summary' => "Geopolitical risk analysis: The event signals elevated market uncertainty with a {$sentiment} outlook. Key assets " . implode(', ', $assets) . " are likely to experience increased volatility and directional pressure based on the severity and scope of this development.",
            'risk_factors' => $riskFactors,
            'geopolitical_analysis' => "This event represents a significant development in the geopolitical landscape. Market participants should monitor escalation pathways and official responses from key stakeholders. The strategic implications extend beyond immediate market reactions, potentially reshaping trade flows, energy markets, and currency dynamics in the affected region. Historical patterns suggest that events of this nature tend to create persistent volatility premiums in affected asset classes, with risk-off sentiment dominating near-term price action while creating potential medium-term opportunities in safe-haven assets and commodities.",
            'timeline_projection' => [
                'short_term' => 'Expect heightened volatility and risk-off positioning across major asset classes within the next 0-2 weeks.',
                'medium_term' => 'Market participants will reassess risk premiums and adjust portfolios as the situation evolves over 1-3 months.',
                'long_term' => 'Structural shifts in trade relationships and security alliances may reshape market dynamics over the 3-12 month horizon.',
            ],
            'historical_parallels' => [
                ['event' => 'Similar geopolitical escalation', 'year' => 2022, 'outcome' => 'Significant risk-off rotation with commodities surging and equities declining sharply.'],
            ],
        ];
    }
}
