<?php

namespace App\Services;

use Illuminate\Support\Str;

class MarketImpactScoringEngine
{
    protected const ECONOMIC_EVENT_SCORES = [
        'fed rate decision' => 95,
        'fed rate' => 95,
        'rate decision' => 95,
        'nfp' => 90,
        'non-farm payrolls' => 90,
        'cpi' => 85,
        'consumer price index' => 85,
        'core cpi' => 85,
        'gdp' => 80,
        'pmi' => 75,
        'ppi' => 75,
        'retail sales' => 70,
        'unemployment claims' => 70,
    ];

    protected const GEOPOLITICAL_EVENT_SCORES = [
        'war escalation' => 95,
        'military escalation' => 90,
        'oil shock' => 90,
        'trade war' => 85,
        'sanctions' => 80,
        'energy crisis' => 90,
        'banking crisis' => 85,
        'political election' => 70,
    ];

    public function calculateMarketImpact(array $payload): array
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
            $scores[] = $this->scoreEvent((string) $event, 'economic');
        }

        if (empty($scores)) {
            return ['score' => 50, 'direction' => 'neutral', 'items' => []];
        }

        $average = (int) round(array_sum(array_column($scores, 'score')) / count($scores));
        $direction = $this->deriveDirection(array_column($scores, 'direction'));

        return ['score' => $average, 'direction' => $direction, 'items' => $scores];
    }

    public function scoreGeopoliticalEvents(array $events): array
    {
        $scores = [];

        foreach ($events as $event) {
            $scores[] = $this->scoreEvent((string) $event, 'geopolitical');
        }

        if (empty($scores)) {
            return ['score' => 50, 'direction' => 'neutral', 'items' => []];
        }

        $average = (int) round(array_sum(array_column($scores, 'score')) / count($scores));
        $direction = $this->deriveDirection(array_column($scores, 'direction'));

        return ['score' => $average, 'direction' => $direction, 'items' => $scores];
    }

    public function scoreEvent(string $name, string $type): array
    {
        $normalized = strtolower($name);
        $score = $this->findBaseScore($normalized, $type);
        $direction = $this->deriveEventDirection($normalized, $type);

        return [
            'source' => $name,
            'score' => $score,
            'direction' => $direction,
        ];
    }

    protected function findBaseScore(string $normalized, string $type): int
    {
        $lookup = $type === 'geopolitical' ? self::GEOPOLITICAL_EVENT_SCORES : self::ECONOMIC_EVENT_SCORES;

        foreach ($lookup as $keyword => $score) {
            if (Str::contains($normalized, $keyword)) {
                return $score;
            }
        }

        return 70;
    }

    protected function deriveEventDirection(string $normalized, string $type): string
    {
        if ($type === 'geopolitical') {
            return Str::contains($normalized, ['war', 'escalation', 'shock', 'crisis', 'sanction']) ? 'bearish' : 'neutral';
        }

        if (Str::contains($normalized, ['beat', 'strong', 'recovery', 'positive'])) {
            return 'bullish';
        }

        if (Str::contains($normalized, ['miss', 'weak', 'downturn', 'recession', 'risk', 'tightening', 'slowdown'])) {
            return 'bearish';
        }

        return 'neutral';
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
            $base = 50 + min(35, abs($change) * 20) + min(15, $volume / 1_000_000);
            $scores[] = $this->clampScore((int) round($base));
        }

        $average = (int) round(array_sum($scores) / count($scores));
        $sentiment = $average > 55 ? 'bullish' : ($average < 45 ? 'bearish' : 'neutral');

        return ['score' => $average, 'sentiment' => $sentiment, 'items' => $rows];
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
            $positive += $this->countKeywords($text, ['bullish', 'gain', 'rally', 'strong', 'beat', 'outperform']);
            $negative += $this->countKeywords($text, ['bearish', 'weak', 'miss', 'decline', 'risk', 'crash', 'drop', 'shock']);
        }

        $score = $this->clampScore(50 + ($positive - $negative) * 5);
        $sentiment = $score > 55 ? 'bullish' : ($score < 45 ? 'bearish' : 'neutral');

        return ['score' => $score, 'sentiment' => $sentiment, 'items' => $items];
    }

    protected function deriveSignal(int $impactScore, string $economicDirection, string $geopoliticalDirection): string
    {
        if ($impactScore >= 65 && $geopoliticalDirection !== 'bearish') {
            return 'bullish';
        }

        if ($impactScore <= 45 || $geopoliticalDirection === 'bearish') {
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
        $weighted = 0.45 * $economic + 0.35 * $geopolitical + 0.12 * $market + 0.08 * $news;

        return $this->clampScore((int) round($weighted));
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
