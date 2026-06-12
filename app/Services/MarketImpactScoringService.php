<?php

namespace App\Services;

use App\Models\EconomicEvent;
use App\Models\GeopoliticalEvent;
use App\Models\EconomicIndicator;
use App\Models\GeopoliticalEventType;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class MarketImpactScoringService
{
    protected const ECONOMIC_BASE_SCORES = [
        'interest_rate' => 95,
        'nfp' => 90,
        'cpi' => 85,
        'core_cpi' => 85,
        'gdp' => 80,
        'pmi' => 75,
        'ppi' => 75,
        'retail_sales' => 70,
        'unemployment_claims' => 70,
    ];

    protected const GEOPOLITICAL_BASE_SCORES = [
        'war' => 95,
        'military_escalation' => 90,
        'energy_crisis' => 90,
        'trade_war' => 85,
        'sanctions' => 80,
        'banking_crisis' => 85,
        'political_election' => 70,
    ];

    protected const EVENT_KEYWORD_SCORES = [
        'fed rate decision' => 95,
        'fed rate' => 95,
        'rate decision' => 95,
        'nfp' => 90,
        'non-farm payrolls' => 90,
        'cpi' => 85,
        'consumer price index' => 85,
        'oil shock' => 90,
        'war escalation' => 95,
        'military escalation' => 90,
        'trade war' => 85,
        'sanctions' => 80,
        'oil crisis' => 90,
        'oil supply cut' => 90,
    ];

    public function calculateImpact(array $payload): array
    {
        $economic = $this->scoreEconomicEvents($payload['economic_events'] ?? []);
        $geopolitical = $this->scoreGeopoliticalEvents($payload['geopolitical_events'] ?? []);
        $market = $this->scoreMarketData($payload['market_data'] ?? []);
        $news = $this->scoreNewsItems($payload['financial_news'] ?? []);

        $impactScore = $this->aggregateImpactScore($economic['score'], $geopolitical['score'], $market['score'], $news['score']);
        $signal = $this->deriveSignal($impactScore, $economic['direction'], $geopolitical['direction']);
        $riskLevel = $this->deriveRiskLevel($economic['score'], $geopolitical['score'], $market['score']);
        $marketSentiment = $this->deriveMarketSentiment($signal, $news['sentiment']);

        return [
            'impact_score' => $impactScore,
            'signal' => $signal,
            'risk_level' => $riskLevel,
            'market_sentiment' => $marketSentiment,
            'breakdown' => [
                'economic' => $economic,
                'geopolitical' => $geopolitical,
                'market' => $market,
                'news' => $news,
            ],
        ];
    }

    public function scoreEconomicEvents(array $events): array
    {
        $scores = [];
        foreach ($events as $event) {
            $scores[] = $this->scoreEconomicEvent($event);
        }

        if (empty($scores)) {
            return [
                'score' => 50,
                'direction' => 'neutral',
                'items' => [],
            ];
        }

        $average = round(collect($scores)->pluck('score')->avg());
        $direction = $this->deriveDirection(collect($scores)->pluck('direction')->toArray());

        return [
            'score' => $average,
            'direction' => $direction,
            'items' => $scores,
        ];
    }

    public function scoreGeopoliticalEvents(array $events): array
    {
        $scores = [];
        foreach ($events as $event) {
            $scores[] = $this->scoreGeopoliticalEvent($event);
        }

        if (empty($scores)) {
            return [
                'score' => 50,
                'direction' => 'neutral',
                'items' => [],
            ];
        }

        $average = round(collect($scores)->pluck('score')->avg());
        $direction = $this->deriveDirection(collect($scores)->pluck('direction')->toArray());

        return [
            'score' => $average,
            'direction' => $direction,
            'items' => $scores,
        ];
    }

    public function scoreEconomicEvent(EconomicEvent|string $event): array
    {
        if ($event instanceof EconomicEvent) {
            $name = $event->event_name;
            $indicator = $event->indicator_type;
            $importance = $event->importance;
            $surprise = $event->surprise;
        } else {
            $name = (string) $event;
            $indicator = $this->resolveEconomicIndicatorCode($name);
            $importance = 'high';
            $surprise = null;
        }

        $base = $this->getEconomicBaseScore($indicator, $name);
        $modifier = $this->getEconomicSurpriseModifier($indicator, $surprise, $importance);
        $score = $this->clampScore($base + $modifier);
        $direction = $this->deriveEconomicDirection($indicator, $surprise, $name);

        return [
            'source' => $name,
            'indicator' => $indicator,
            'score' => $score,
            'direction' => $direction,
            'importance' => $importance,
            'surprise' => $surprise,
        ];
    }

    public function scoreGeopoliticalEvent(GeopoliticalEvent|string $event): array
    {
        if ($event instanceof GeopoliticalEvent) {
            $name = $event->title;
            $type = $event->event_type;
            $severity = $event->severity;
            $escalation = $event->escalation_level ?? 0;
        } else {
            $name = (string) $event;
            $type = $this->resolveGeopoliticalTypeCode($name);
            $severity = 'high';
            $escalation = Str::contains(strtolower($name), 'escalation') ? 2 : 1;
        }

        $base = $this->getGeopoliticalBaseScore($type, $name);
        $modifier = $this->getGeopoliticalSeverityModifier($severity, $escalation);
        $score = $this->clampScore($base + $modifier);
        $direction = 'bearish';

        return [
            'source' => $name,
            'type' => $type,
            'score' => $score,
            'direction' => $direction,
            'severity' => $severity,
            'escalation' => $escalation,
        ];
    }

    protected function scoreMarketData(array $rows): array
    {
        if (empty($rows)) {
            return ['score' => 50, 'sentiment' => 'neutral', 'items' => []];
        }

        $scores = [];
        foreach ($rows as $row) {
            $change = isset($row['change']) ? (float) $row['change'] : 0;
            $volume = isset($row['volume']) ? (float) $row['volume'] : 0;
            $score = 50 + min(45, abs($change) * 20) + min(5, $volume / 1_000_000);
            $scores[] = $this->clampScore($score);
        }

        $average = round(collect($scores)->avg());
        $sentiment = $average > 55 ? 'bullish' : ($average < 45 ? 'bearish' : 'neutral');

        return [
            'score' => $average,
            'sentiment' => $sentiment,
            'items' => $rows,
        ];
    }

    protected function scoreNewsItems(array $items): array
    {
        if (empty($items)) {
            return ['score' => 50, 'sentiment' => 'neutral', 'items' => []];
        }

        $positive = 0;
        $negative = 0;
        foreach ($items as $item) {
            $text = strtolower((string) $item);
            $positive += $this->countKeywords($text, ['gain', 'positive', 'bullish', 'outperform', 'rally', 'strong', 'beat']);
            $negative += $this->countKeywords($text, ['drop', 'bearish', 'weak', 'miss', 'risk', 'crash', 'decline', 'shock', 'sell']);
        }

        $score = $this->clampScore(50 + (($positive - $negative) * 5));
        $sentiment = $score > 55 ? 'bullish' : ($score < 45 ? 'bearish' : 'neutral');

        return [
            'score' => $score,
            'sentiment' => $sentiment,
            'items' => $items,
        ];
    }

    protected function resolveEconomicIndicatorCode(string $name): ?string
    {
        $normalized = strtolower($name);
        foreach (self::EVENT_KEYWORD_SCORES as $keyword => $score) {
            if (Str::contains($normalized, $keyword)) {
                return EconomicIndicator::classify($keyword) ?? null;
            }
        }

        return EconomicIndicator::classify($name);
    }

    protected function resolveGeopoliticalTypeCode(string $name): ?string
    {
        $candidate = GeopoliticalEventType::classify($name);
        if ($candidate) {
            return $candidate;
        }

        $normalized = strtolower($name);
        foreach (self::EVENT_KEYWORD_SCORES as $keyword => $score) {
            if (Str::contains($normalized, $keyword)) {
                return match ($keyword) {
                    'war escalation', 'military escalation' => 'military_escalation',
                    'oil shock', 'oil crisis', 'oil supply cut' => 'energy_crisis',
                    'trade war' => 'trade_war',
                    'sanctions' => 'sanctions',
                    default => null,
                };
            }
        }

        return null;
    }

    protected function getEconomicBaseScore(?string $indicator, string $name): int
    {
        if ($indicator && isset(self::ECONOMIC_BASE_SCORES[$indicator])) {
            return self::ECONOMIC_BASE_SCORES[$indicator];
        }

        $normalized = strtolower($name);
        foreach (self::EVENT_KEYWORD_SCORES as $keyword => $score) {
            if (Str::contains($normalized, $keyword)) {
                return $score;
            }
        }

        return 70;
    }

    protected function getGeopoliticalBaseScore(?string $type, string $name): int
    {
        if ($type && isset(self::GEOPOLITICAL_BASE_SCORES[$type])) {
            return self::GEOPOLITICAL_BASE_SCORES[$type];
        }

        $normalized = strtolower($name);
        foreach (self::EVENT_KEYWORD_SCORES as $keyword => $score) {
            if (Str::contains($normalized, $keyword)) {
                return $score;
            }
        }

        return 70;
    }

    protected function getEconomicSurpriseModifier(?string $indicator, $surprise, string $importance): int
    {
        if ($surprise === null) {
            return $importance === 'high' ? 5 : 0;
        }

        $surpriseValue = abs((float) $surprise);
        $modifier = min(15, round($surpriseValue * 20));

        if ($indicator === 'cpi' || $indicator === 'core_cpi' || $indicator === 'ppi') {
            return $modifier;
        }

        if ($indicator === 'nfp' || $indicator === 'gdp' || $indicator === 'pmi') {
            return round($modifier * 0.8);
        }

        return $modifier;
    }

    protected function getGeopoliticalSeverityModifier(string $severity = 'medium', int $escalation = 0): int
    {
        $base = match ($severity) {
            'critical' => 15,
            'high' => 10,
            'medium' => 5,
            default => 0,
        };

        return $base + ($escalation * 3);
    }

    protected function deriveSignal(int $score, string $economicDirection, string $geopoliticalDirection): string
    {
        if ($score >= 65 && $geopoliticalDirection !== 'bearish') {
            return 'bullish';
        }

        if ($score <= 40 || $geopoliticalDirection === 'bearish') {
            return 'bearish';
        }

        return 'neutral';
    }

    protected function deriveRiskLevel(int $economicScore, int $geopoliticalScore, int $marketScore): string
    {
        if ($geopoliticalScore >= 90 || $economicScore >= 90 || $marketScore >= 90) {
            return 'high';
        }

        if ($geopoliticalScore >= 75 || $economicScore >= 75 || $marketScore >= 75) {
            return 'medium';
        }

        return 'low';
    }

    protected function deriveMarketSentiment(string $signal, string $newsSentiment): string
    {
        if ($signal === 'bullish' && $newsSentiment === 'bullish') {
            return 'bullish';
        }

        if ($signal === 'bearish' || $newsSentiment === 'bearish') {
            return 'bearish';
        }

        return 'neutral';
    }

    protected function aggregateImpactScore(int $economic, int $geopolitical, int $market, int $news): int
    {
        $weight = 0.45 * $economic + 0.35 * $geopolitical + 0.12 * $market + 0.08 * $news;
        return $this->clampScore((int) round($weight));
    }

    protected function deriveDirection(array $directions): string
    {
        if (empty($directions)) {
            return 'neutral';
        }

        $counts = array_count_values($directions);
        arsort($counts);

        return array_key_first($counts);
    }

    protected function deriveEconomicDirection(?string $indicator, $surprise, string $name): string
    {
        if ($surprise === null) {
            return 'neutral';
        }

        $positive = (float) $surprise > 0;
        if (in_array($indicator, ['cpi', 'core_cpi', 'ppi']) || Str::contains(strtolower($name), ['inflation', 'cpi', 'price index'])) {
            return $positive ? 'bearish' : 'bullish';
        }

        if (in_array($indicator, ['nfp', 'gdp', 'pmi', 'retail_sales'])) {
            return $positive ? 'bullish' : 'bearish';
        }

        return 'neutral';
    }

    protected function clampScore(int $score): int
    {
        return max(0, min(100, $score));
    }

    protected function countKeywords(string $text, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $keyword) {
            if (Str::contains($text, $keyword)) {
                $count++;
            }
        }
        return $count;
    }
}
