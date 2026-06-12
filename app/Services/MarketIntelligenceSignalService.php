<?php

namespace App\Services;

use Illuminate\Support\Str;

class MarketIntelligenceSignalService
{
    public function aggregateSources(array $payload): array
    {
        return [
            'economic' => $this->summarizeTextSources($payload['economic_events'] ?? []),
            'market' => $this->summarizeMarketData($payload['market_data'] ?? []),
            'news' => $this->summarizeTextSources($payload['financial_news'] ?? []),
            'geopolitical' => $this->summarizeTextSources($payload['geopolitical_events'] ?? []),
        ];
    }

    public function deriveImpactScore(array $sourceSummaries): int
    {
        $weights = [
            'economic' => 0.30,
            'market' => 0.35,
            'news' => 0.20,
            'geopolitical' => 0.15,
        ];

        $score = 0;
        foreach ($weights as $source => $weight) {
            $score += ($sourceSummaries[$source]['strength'] ?? 50) * $weight;
        }

        return (int) round(max(0, min(100, $score)));
    }

    public function deriveSignal(array $sourceSummaries): string
    {
        $score = $this->deriveImpactScore($sourceSummaries);

        return $score >= 60 ? 'bullish' : ($score <= 40 ? 'bearish' : 'neutral');
    }

    public function deriveRiskLevel(array $sourceSummaries): string
    {
        if ($sourceSummaries['geopolitical']['risk'] === 'high' || $sourceSummaries['economic']['risk'] === 'high') {
            return 'high';
        }

        if ($sourceSummaries['market']['risk'] === 'medium' || $sourceSummaries['news']['risk'] === 'medium') {
            return 'medium';
        }

        return 'low';
    }

    public function deriveMarketSentiment(array $sourceSummaries): string
    {
        $bullish = $sourceSummaries['market']['sentiment_score'] + $sourceSummaries['news']['sentiment_score'];
        $bearish = $sourceSummaries['economic']['sentiment_score'] + $sourceSummaries['geopolitical']['sentiment_score'];
        $total = $bullish - $bearish;

        return $total > 10 ? 'bullish' : ($total < -10 ? 'bearish' : 'neutral');
    }

    protected function summarizeTextSources(array $items): array
    {
        $text = trim(implode("\n", array_map([$this, 'normalizeText'], $items)));
        if ($text === '') {
            return [
                'count' => 0,
                'sentiment_score' => 50,
                'strength' => 50,
                'risk' => 'low',
                'summary' => 'No data available.',
            ];
        }

        $sentimentScore = $this->calculateTextSentimentScore($text);
        $risk = $this->assessTextRisk($text);
        $strength = $this->calculateTextStrength($text, $sentimentScore);
        $summary = $this->buildTextSummary($text, $sentimentScore, $risk);

        return [
            'count' => count($items),
            'sentiment_score' => $sentimentScore,
            'strength' => $strength,
            'risk' => $risk,
            'summary' => $summary,
        ];
    }

    protected function summarizeMarketData(array $rows): array
    {
        if (empty($rows)) {
            return [
                'count' => 0,
                'sentiment_score' => 50,
                'strength' => 50,
                'risk' => 'low',
                'summary' => 'No market data available.',
            ];
        }

        $totalChange = 0;
        $positive = 0;
        $negative = 0;
        $volumeImpact = 0;
        $count = 0;

        foreach ($rows as $row) {
            $change = isset($row['change']) ? (float) $row['change'] : null;
            $volume = isset($row['volume']) ? (float) $row['volume'] : null;
            if ($change === null) {
                continue;
            }
            $count++;
            $totalChange += $change;
            if ($change > 0) {
                $positive++;
            } elseif ($change < 0) {
                $negative++;
            }
            if ($volume !== null) {
                $volumeImpact += min(100, max(0, $volume / 1_000_000));
            }
        }

        $averageChange = $count > 0 ? $totalChange / $count : 0;
        $sentimentScore = 50 + round($averageChange * 20);
        $strength = 50 + round((($positive - $negative) / max(1, $count)) * 20);
        $risk = abs($averageChange) > 1 ? 'high' : (abs($averageChange) > 0.3 ? 'medium' : 'low');
        $summary = $this->buildMarketSummary($rows, $averageChange, $volumeImpact);

        return [
            'count' => $count,
            'sentiment_score' => max(0, min(100, $sentimentScore)),
            'strength' => max(0, min(100, $strength)),
            'risk' => $risk,
            'summary' => $summary,
        ];
    }

    protected function calculateTextSentimentScore(string $text): int
    {
        $positiveKeywords = ['support', 'strong', 'outperform', 'upside', 'bullish', 'recovery', 'gain', 'surge'];
        $negativeKeywords = ['risk', 'sell', 'downside', 'bearish', 'weak', 'drop', 'decline', 'crash', 'recession', 'inflation'];

        $score = 50;
        foreach ($positiveKeywords as $keyword) {
            if (Str::contains($text, $keyword)) {
                $score += 5;
            }
        }
        foreach ($negativeKeywords as $keyword) {
            if (Str::contains($text, $keyword)) {
                $score -= 5;
            }
        }

        return max(0, min(100, $score));
    }

    protected function assessTextRisk(string $text): string
    {
        if (Str::contains($text, ['crash', 'recession', 'default', 'war', 'sanction', 'credit crunch'])) {
            return 'high';
        }

        if (Str::contains($text, ['volatility', 'uncertainty', 'inflation', 'tightening', 'slowdown'])) {
            return 'medium';
        }

        return 'low';
    }

    protected function calculateTextStrength(string $text, int $sentimentScore): int
    {
        $count = max(1, str_word_count($text));
        return max(20, min(100, 40 + round($sentimentScore / 2) + min(30, (int) log($count + 1) * 4)));
    }

    protected function buildTextSummary(string $text, int $sentimentScore, string $risk): string
    {
        $sentiment = $sentimentScore >= 60 ? 'positive' : ($sentimentScore <= 40 ? 'negative' : 'neutral');
        $snippet = Str::limit($text, 180, '...');

        return "Detected a {$sentiment} tone with {$risk} risk. Key drivers include: {$snippet}";
    }

    protected function buildMarketSummary(array $rows, float $averageChange, float $volumeImpact): string
    {
        $direction = $averageChange > 0 ? 'upward' : ($averageChange < 0 ? 'downward' : 'flat');
        $volumeText = $volumeImpact > 150 ? 'elevated trading volume' : ($volumeImpact > 50 ? 'moderate volume' : 'light volume');

        return "Market prices are moving {$direction} with an average change of " . number_format($averageChange, 4) . ". The activity is accompanied by {$volumeText}.";
    }

    protected function normalizeText($value): string
    {
        if (is_array($value)) {
            return $this->normalizeText(implode(' ', $value));
        }

        return trim(Str::of((string) $value)->replaceMatches('/\s+/', ' '));
    }
}
