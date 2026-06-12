<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI;

class MarketIntelligenceAIService
{
    public function analyze(array $payload): ?array
    {
        if (!$this->hasApiKey()) {
            return null;
        }

        try {
            $client = OpenAI::client(Config::get('ai.openai_api_key'));
            $response = $client->chat()->create([
                'model' => Config::get('ai.openai_model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a senior market intelligence analyst specializing in macroeconomic, financial market, and geopolitical impact assessment. Always respond with valid JSON.'],
                    ['role' => 'user', 'content' => $this->buildPrompt($payload)],
                ],
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 500,
            ]);

            $content = $response->choices[0]->message->content ?? null;
            $result = json_decode($content, true);

            if (!is_array($result)) {
                return null;
            }

            return $this->normalizeResult($result);
        } catch (\Exception $exception) {
            Log::error('MarketIntelligenceAIService failed: ' . $exception->getMessage());
            return null;
        }
    }

    protected function hasApiKey(): bool
    {
        $apiKey = Config::get('ai.openai_api_key');

        return !empty($apiKey) && !Str::contains($apiKey, 'your-openai');
    }

    protected function buildPrompt(array $payload): string
    {
        $economic = implode("\n", array_filter(array_map([$this, 'stringifyItem'], $payload['economic_events'] ?? [])));
        $market = implode("\n", array_filter(array_map([$this, 'stringifyMarketRow'], $payload['market_data'] ?? [])));
        $news = implode("\n", array_filter(array_map([$this, 'stringifyItem'], $payload['financial_news'] ?? [])));
        $geopolitical = implode("\n", array_filter(array_map([$this, 'stringifyItem'], $payload['geopolitical_events'] ?? [])));

        return trim("
Please analyze the following market intelligence inputs and return a valid JSON object with these exact keys:
- impact_score: integer between 0 and 100
- signal: one of 'bullish', 'bearish', 'neutral'
- risk_level: one of 'low', 'medium', 'high'
- market_sentiment: one of 'bullish', 'bearish', 'neutral'
- confidence: integer between 0 and 100
- primary_drivers: array of up to 5 strings
- explanation: 2-3 sentence summary
- recommendation: 1-2 sentence tactical recommendation

Economic Events:
{$economic}

Market Data:
{$market}

Financial News:
{$news}

Geopolitical Events:
{$geopolitical}

Focus on macroeconomic and geopolitical risk, then market price action and news sentiment. Always answer using valid JSON only.");
    }

    protected function stringifyItem($item): string
    {
        if (is_array($item)) {
            return implode(' | ', array_filter(array_map('trim', array_map('strval', $item))));
        }

        return trim((string) $item);
    }

    protected function stringifyMarketRow($row): string
    {
        if (!is_array($row)) {
            return trim((string) $row);
        }

        return implode(' | ', array_filter([
            $row['symbol'] ?? null,
            isset($row['price']) ? 'price: ' . $row['price'] : null,
            isset($row['change']) ? 'change: ' . $row['change'] : null,
            isset($row['volume']) ? 'volume: ' . $row['volume'] : null,
            $row['timestamp'] ?? null,
        ]));
    }

    protected function normalizeResult(array $result): array
    {
        return [
            'impact_score' => isset($result['impact_score']) ? (int) $result['impact_score'] : 50,
            'signal' => $this->normalizeSignal($result['signal'] ?? null),
            'risk_level' => $this->normalizeRiskLevel($result['risk_level'] ?? null),
            'market_sentiment' => $this->normalizeSignal($result['market_sentiment'] ?? null),
            'confidence' => isset($result['confidence']) ? (int) $result['confidence'] : null,
            'primary_drivers' => isset($result['primary_drivers']) && is_array($result['primary_drivers']) ? array_values(array_slice($result['primary_drivers'], 0, 5)) : [],
            'explanation' => trim((string) ($result['explanation'] ?? '')),
            'recommendation' => trim((string) ($result['recommendation'] ?? '')),
        ];
    }

    protected function normalizeSignal(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['bullish', 'bearish', 'neutral'], true) ? $value : 'neutral';
    }

    protected function normalizeRiskLevel(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['low', 'medium', 'high'], true) ? $value : 'medium';
    }
}
