<?php

namespace App\Services;

use Illuminate\Support\Str;

class MarketIntelligenceAnalysisService
{
    protected MarketImpactScoringService $impactScoring;
    protected MarketIntelligenceSignalService $signalService;
    protected MarketIntelligenceAIService $aiService;

    public function __construct(
        MarketImpactScoringService $impactScoring,
        MarketIntelligenceSignalService $signalService,
        MarketIntelligenceAIService $aiService
    ) {
        $this->impactScoring = $impactScoring;
        $this->signalService = $signalService;
        $this->aiService = $aiService;
    }

    public function analyze(array $input): array
    {
        $payload = $this->normalizePayload($input);
        $breakdown = $this->impactScoring->calculateImpact($payload);

        $sourceSummaries = $this->signalService->aggregateSources($payload);
        $localOutput = $this->buildLocalOutput($breakdown, $sourceSummaries);

        $aiOutput = $this->aiService->analyze($payload);
        if (is_array($aiOutput)) {
            return array_merge($localOutput, [
                'ai' => [
                    'enabled' => true,
                    'confidence' => $aiOutput['confidence'] ?? null,
                ],
                'primary_drivers' => $aiOutput['primary_drivers'],
                'explanation' => $aiOutput['explanation'],
                'recommendation' => $aiOutput['recommendation'],
            ], $aiOutput);
        }

        return $localOutput;
    }

    protected function normalizePayload(array $input): array
    {
        return [
            'economic_events' => $this->normalizeList($input['economic_events'] ?? []),
            'market_data' => $this->normalizeMarketData($input['market_data'] ?? []),
            'financial_news' => $this->normalizeList($input['financial_news'] ?? []),
            'geopolitical_events' => $this->normalizeList($input['geopolitical_events'] ?? []),
        ];
    }

    protected function normalizeList($items): array
    {
        if (is_string($items)) {
            return array_filter(array_map('trim', explode("\n", $items)));
        }

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (is_array($item)) {
                return trim(implode(' | ', array_filter(array_map('strval', $item))));
            }

            return is_string($item) ? trim($item) : null;
        }, $items)));
    }

    protected function normalizeMarketData($items): array
    {
        if (!is_array($items)) {
            return [];
        }

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
        }, $items)));
    }

    protected function buildLocalOutput(array $breakdown, array $sourceSummaries): array
    {
        $impactScore = $breakdown['impact_score'];
        $signal = $breakdown['signal'];
        $riskLevel = $breakdown['risk_level'];
        $marketSentiment = $breakdown['market_sentiment'];

        return [
            'impact_score' => $impactScore,
            'signal' => $signal,
            'risk_level' => $riskLevel,
            'market_sentiment' => $marketSentiment,
            'recommendation' => $this->buildRecommendation($signal, $riskLevel),
            'source_breakdown' => $breakdown['breakdown'],
            'summary' => $this->buildSummary($signal, $riskLevel, $marketSentiment, $sourceSummaries),
            'ai' => [
                'enabled' => false,
            ],
        ];
    }

    protected function buildSummary(string $signal, string $riskLevel, string $marketSentiment, array $sourceSummaries): string
    {
        $economicSummary = $sourceSummaries['economic']['summary'] ?? 'Economic signal is unavailable.';
        $marketSummary = $sourceSummaries['market']['summary'] ?? 'Market data is unavailable.';
        $newsSummary = $sourceSummaries['news']['summary'] ?? 'News sentiment is unavailable.';
        $geoSummary = $sourceSummaries['geopolitical']['summary'] ?? 'Geopolitical risk is unavailable.';

        return Str::limit(
            "The analysis points to a {$signal} signal with {$riskLevel} risk and overall market sentiment of {$marketSentiment}. {$economicSummary} {$marketSummary} {$newsSummary} {$geoSummary}",
            420,
            '...'
        );
    }

    protected function buildRecommendation(string $signal, string $riskLevel): string
    {
        if ($riskLevel === 'high') {
            return $signal === 'bullish'
                ? 'Maintain selective exposure, keep position size disciplined, and use hedges to protect from downside risk.'
                : 'Reduce directional risk, preserve cash, and favor defensive or hedged assets until the environment stabilizes.';
        }

        if ($signal === 'bullish') {
            return 'Consider tactical longs in high-quality assets while monitoring macro and geopolitical developments closely.';
        }

        if ($signal === 'bearish') {
            return 'Favor capital preservation, focus on defensives, and wait for clearer market confirmation before adding risk.';
        }

        return 'Keep a balanced stance, emphasize risk management, and wait for more decisive signals before increasing exposure.';
    }
}
