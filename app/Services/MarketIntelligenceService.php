<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use OpenAI;

class MarketIntelligenceService
{
    protected MarketIntelligenceSignalService $signalService;

    public function __construct(MarketIntelligenceSignalService $signalService)
    {
        $this->signalService = $signalService;
    }

    public function analyze(array $input): array
    {
        $payload = $this->normalizePayload($input);
        $sourceSummaries = $this->signalService->aggregateSources($payload);

        if ($this->hasApiKey()) {
            return $this->analyzeWithAI($payload, $sourceSummaries);
        }

        return $this->analyzeLocally($payload, $sourceSummaries);
    }

    protected function normalizePayload(array $input): array
    {
        return [
            'economic_events' => $this->normalizeList($input['economic_events'] ?? []),
            'market_data' => $this->normalizeModels($input['market_data'] ?? []),
            'financial_news' => $this->normalizeList($input['financial_news'] ?? []),
            'geopolitical_events' => $this->normalizeList($input['geopolitical_events'] ?? []),
        ];
    }

    protected function normalizeList($value): array
    {
        if (is_string($value)) {
            return array_filter(array_map('trim', explode("\n", $value)));
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map(function ($item) {
                return is_string($item) ? trim($item) : (is_array($item) ? trim(implode(' ', $item)) : null);
            }, $value)));
        }

        return [];
    }

    protected function normalizeModels($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($record) {
            if (!is_array($record)) {
                return null;
            }

            return [
                'symbol' => $record['symbol'] ?? null,
                'price' => isset($record['price']) ? (float) $record['price'] : null,
                'change' => isset($record['change']) ? (float) $record['change'] : null,
                'volume' => isset($record['volume']) ? (float) $record['volume'] : null,
                'timestamp' => $record['timestamp'] ?? null,
            ];
        }, $value)));
    }

    protected function hasApiKey(): bool
    {
        return !empty(Config::get('ai.openai_api_key')) && !Str::contains(Config::get('ai.openai_api_key'), 'your-openai');
    }

    protected function analyzeWithAI(array $payload, array $sourceSummaries): array
    {
        try {
            $client = OpenAI::client(Config::get('ai.openai_api_key'));
            $prompt = $this->buildPrompt($payload, $sourceSummaries);

            $response = $client->chat()->create([
                'model' => Config::get('ai.openai_model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a senior market intelligence analyst specialized in macroeconomic, financial, and geopolitical risk. Always respond with valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content ?? null;
            $result = json_decode($content, true);

            if (!is_array($result)) {
                return $this->analyzeLocally($payload, $sourceSummaries);
            }

            return $this->hydrateOutput($result, $payload, $sourceSummaries);
        } catch (\Exception $e) {
            Log::error('Market Intelligence AI analysis failed: ' . $e->getMessage());
            return $this->analyzeLocally($payload, $sourceSummaries);
        }
    }

    protected function analyzeLocally(array $payload, array $sourceSummaries): array
    {
        $impactScore = $this->signalService->deriveImpactScore($sourceSummaries);
        $signal = $this->signalService->deriveSignal($sourceSummaries);
        $riskLevel = $this->signalService->deriveRiskLevel($sourceSummaries);
        $marketSentiment = $this->signalService->deriveMarketSentiment($sourceSummaries);

        return [
            'impact_score' => $impactScore,
            'signal' => $signal,
            'risk_level' => $riskLevel,
            'market_sentiment' => $marketSentiment,
            'output_summary' => $this->buildSummary($sourceSummaries, $impactScore, $signal, $riskLevel, $marketSentiment),
            'source_breakdown' => $sourceSummaries,
            'recommendation' => $this->buildRecommendation($signal, $riskLevel),
        ];
    }

    protected function hydrateOutput(array $result, array $payload, array $sourceSummaries): array
    {
        return [
            'impact_score' => isset($result['impact_score']) ? (int) $result['impact_score'] : $this->signalService->deriveImpactScore($sourceSummaries),
            'signal' => $result['signal'] ?? $this->signalService->deriveSignal($sourceSummaries),
            'risk_level' => $result['risk_level'] ?? $this->signalService->deriveRiskLevel($sourceSummaries),
            'market_sentiment' => $result['market_sentiment'] ?? $this->signalService->deriveMarketSentiment($sourceSummaries),
            'ai_confidence' => $result['confidence'] ?? null,
            'impact_explanation' => $result['explanation'] ?? ($result['market_summary'] ?? null),
            'primary_drivers' => $result['primary_drivers'] ?? $this->extractTopDrivers($sourceSummaries),
            'source_breakdown' => $sourceSummaries,
            'recommendation' => $result['recommendation'] ?? $this->buildRecommendation($result['signal'] ?? $this->signalService->deriveSignal($sourceSummaries), $result['risk_level'] ?? $this->signalService->deriveRiskLevel($sourceSummaries)),
        ];
    }

    protected function buildPrompt(array $payload, array $sourceSummaries): string
    {
        $economicText = implode("\n", $payload['economic_events']);
        $marketData = collect($payload['market_data'])->map(function ($row) {
            return implode(' | ', array_filter([
                $row['symbol'] ?? 'unknown',
                isset($row['price']) ? 'price: ' . $row['price'] : null,
                isset($row['change']) ? 'change: ' . $row['change'] : null,
                isset($row['volume']) ? 'volume: ' . $row['volume'] : null,
            ]));
        })->implode("\n");
        $newsText = implode("\n", $payload['financial_news']);
        $geoText = implode("\n", $payload['geopolitical_events']);

        return trim("
Please analyze the following inputs and return valid JSON with the exact keys:
- impact_score: integer between 0 and 100
- signal: one of 'bullish', 'bearish', 'neutral'
- risk_level: one of 'low', 'medium', 'high'
- market_sentiment: one of 'bullish', 'bearish', 'neutral'
- confidence: integer between 0 and 100
- primary_drivers: array of up to 5 strings
- explanation: 2-3 sentence summary of the reasoning
- recommendation: 1-2 sentence tactical market intelligence recommendation

Economic Events:
{$economicText}

Market Data:
{$marketData}

Financial News:
{$newsText}

Geopolitical Events:
{$geoText}

When you evaluate the inputs, prioritize macroeconomic and geopolitical risk factors, then market price flows and news sentiment. Keep answers factual and concise.
");
    }

    protected function buildSummary(array $sourceSummaries, int $impactScore, string $signal, string $riskLevel, string $marketSentiment): string
    {
        return Str::of("The combined intelligence indicates a {$signal} signal with a {$riskLevel} risk profile and overall market sentiment of {$marketSentiment}. The aggregated impact score is {$impactScore}. ")
            ->append($sourceSummaries['economic']['summary'] . ' ')
            ->append($sourceSummaries['market']['summary'] . ' ')
            ->append($sourceSummaries['geopolitical']['summary'] . ' ')
            ->append($sourceSummaries['news']['summary'])
            ->limit(360)
            ->toString();
    }

    protected function buildRecommendation(string $signal, string $riskLevel): string
    {
        if ($riskLevel === 'high') {
            return $signal === 'bullish'
                ? 'Favor selective exposure while maintaining strict risk controls and consider hedges for downside scenarios.'
                : 'Reduce directional exposure, preserve liquidity, and focus on defensive or hedged positions.';
        }

        if ($signal === 'bullish') {
            return 'Consider tactical long exposure in the strongest assets while watching for volatility and event risk.';
        }

        if ($signal === 'bearish') {
            return 'Lean into defensive positioning and wait for clearer macro confirmation before adding risk.';
        }

        return 'Maintain a balanced stance with emphasis on risk management until sentiment becomes more decisive.';
    }

    protected function extractTopDrivers(array $sourceSummaries): array
    {
        $drivers = [];
        foreach ($sourceSummaries as $source => $summary) {
            if (!empty($summary['summary'])) {
                $drivers[] = Str::limit($summary['summary'], 80, '');
            }
        }

        return array_values(array_filter($drivers));
    }
}
