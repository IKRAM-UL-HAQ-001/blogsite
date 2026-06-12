<?php

namespace App\Services\Providers\Ingestion;

use App\Contracts\NewsProvider;
use App\DTOs\NormalizedNewsItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches global news articles from the GDELT DOC 2.0 API.
 *
 * Free, no API key required. Good for geopolitical discovery.
 * Returns metadata only (title, URL, domain) — no full body.
 *
 * API reference: https://blog.gdeltproject.org/gdelt-doc-2-0-api-debuts/
 */
final class GdeltNewsProvider implements NewsProvider
{
    private const API_BASE = 'https://api.gdeltproject.org/api/v2/doc/doc';
    private const MAX_RECORDS = 250;

    public function __construct(private readonly array $config = []) {}

    /**
     * @return iterable<NormalizedNewsItem>
     */
    public function fetch(CarbonInterface $since): iterable
    {
        $query      = $this->config['query'] ?? 'geopolitical war sanctions oil energy';
        $maxRecords = min((int) ($this->config['max_records'] ?? self::MAX_RECORDS), self::MAX_RECORDS);
        $timespan   = $this->computeTimespan($since);

        $url = self::API_BASE . '?' . http_build_query([
            'query'      => $query,
            'mode'       => 'artlist',
            'format'     => 'json',
            'maxrecords' => $maxRecords,
            'timespan'   => $timespan,
            'sort'       => 'DateDesc',
        ]);

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning("GdeltNewsProvider: HTTP {$response->status()}");
                return;
            }

            $data     = $response->json();
            $articles = $data['articles'] ?? [];

            foreach ($articles as $article) {
                $title = trim($article['title'] ?? '');
                $artUrl = trim($article['url'] ?? '');

                if ($title === '' || $artUrl === '') {
                    continue;
                }

                $seenDate    = $article['seendate'] ?? null;
                $publishedAt = $seenDate
                    ? CarbonImmutable::createFromFormat('YmdHis', $seenDate, 'UTC')
                    : CarbonImmutable::now('UTC');

                if ($publishedAt === false || $publishedAt->isBefore($since)) {
                    continue;
                }

                yield new NormalizedNewsItem(
                    source: 'gdelt',
                    externalId: $artUrl,
                    url: $artUrl,
                    title: $title,
                    summary: null,
                    body: null,
                    author: null,
                    language: $article['language'] ?? 'English',
                    publishedAt: $publishedAt,
                    metadata: [
                        'domain'        => $article['domain'] ?? null,
                        'sourcecountry' => $article['sourcecountry'] ?? null,
                        'tone'          => $article['tone'] ?? null,
                    ],
                );
            }
        } catch (\Throwable $e) {
            Log::error("GdeltNewsProvider: fetch failed — {$e->getMessage()}");
        }
    }

    private function computeTimespan(CarbonInterface $since): string
    {
        $hours = (int) $since->diffInHours(now(), true);
        $hours = max(1, min($hours, 720)); // GDELT max is 30 days
        return $hours . 'h';
    }
}
