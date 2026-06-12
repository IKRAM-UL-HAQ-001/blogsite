<?php

namespace App\Services\Providers\Ingestion;

use App\Contracts\NewsProvider;
use App\DTOs\NormalizedNewsItem;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class RssNewsProvider implements NewsProvider
{
    public function __construct(
        private readonly string $feedUrl,
        private readonly array $config = [],
    ) {}

    /**
     * @return iterable<NormalizedNewsItem>
     */
    public function fetch(CarbonInterface $since): iterable
    {
        $xml = $this->fetchXml($this->feedUrl);

        if ($xml === null) {
            return;
        }

        $items = $xml->channel->item ?? [];

        foreach ($items as $item) {
            $pubDateStr = (string) ($item->pubDate ?? '');
            $publishedAt = $pubDateStr
                ? CarbonImmutable::parse($pubDateStr)
                : CarbonImmutable::now();

            if ($publishedAt->isBefore($since)) {
                continue;
            }

            $url   = trim((string) $item->link);
            $title = trim((string) $item->title);

            if ($url === '' || $title === '') {
                continue;
            }

            $description = (string) ($item->description ?? '');
            // Some feeds nest full content in content:encoded
            $content = (string) ($item->children('content', true)->encoded ?? $description);

            $body    = strip_tags($content ?: $description);
            $summary = $body !== '' ? mb_substr($body, 0, 500) : null;

            $dcCreator = (string) ($item->children('dc', true)->creator ?? '');
            $author    = $dcCreator ?: null;

            yield new NormalizedNewsItem(
                source: $this->feedUrl,
                externalId: null,
                url: $url,
                title: $title,
                summary: $summary,
                body: $body,
                author: $author,
                language: $this->config['language'] ?? 'en',
                publishedAt: $publishedAt,
            );
        }
    }

    private function fetchXml(string $url): ?\SimpleXMLElement
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Accept' => 'application/rss+xml, application/xml, text/xml, */*'])
                ->get($url);

            if (! $response->successful()) {
                Log::warning("RssNewsProvider: HTTP {$response->status()} from {$url}");
                return null;
            }

            $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

            return $xml ?: null;
        } catch (\Throwable $e) {
            Log::warning("RssNewsProvider: failed to fetch {$url} — {$e->getMessage()}");
            return null;
        }
    }
}
