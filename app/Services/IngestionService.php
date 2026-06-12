<?php

namespace App\Services;

use App\Models\NewsSource;
use App\Models\RawArticle;
use App\Models\EconomicEvent;
use App\Models\EconomicIndicator;
use App\Models\GeopoliticalEvent;
use App\Models\GeopoliticalEventType;
use App\Models\IngestionLog;
use App\Jobs\ProcessEconomicEventJob;
use App\Jobs\ProcessGeopoliticalEventJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class IngestionService
{
    protected int $timeout = 30;
    protected int $maxRetries = 2;

    /**
     * Ingest all active news sources, grouped by type.
     * Returns an array of IngestionLog results.
     */
    public function ingestAll(): array
    {
        $results = [];
        $sources = NewsSource::where('is_active', true)->get();

        foreach ($sources as $source) {
            $results[] = $this->ingestSource($source);
        }

        return $results;
    }

    /**
     * Ingest sources of a specific type only.
     */
    public function ingestByType(string $type): array
    {
        $results = [];
        $sources = NewsSource::where('is_active', true)->where('type', $type)->get();

        foreach ($sources as $source) {
            $results[] = $this->ingestSource($source);
        }

        return $results;
    }

    /**
     * Ingest a single source with full logging and error handling.
     */
    public function ingestSource(NewsSource $source): IngestionLog
    {
        $log = IngestionLog::start($source->id, $source->type);
        $log->update(['metadata' => array_merge($log->metadata ?? [], [
            'source_name' => $source->name,
            'source_url' => $source->url,
        ])]);

        Log::info("Ingestion started: {$source->name} (type: {$source->type})");

        try {
            switch ($source->type) {
                case 'economic_calendar':
                    $stats = $this->ingestEconomicCalendarSource($source);
                    break;
                case 'financial':
                    $stats = $this->ingestRssSource($source);
                    break;
                case 'geopolitical':
                    $stats = $this->ingestGeopoliticalSource($source);
                    break;
                case 'market':
                    $stats = $this->ingestMarketSource($source);
                    break;
                case 'commodity':
                    $stats = $this->ingestRssSource($source);
                    break;
                default:
                    throw new \RuntimeException("Unknown source type: {$source->type}");
            }

            $log->complete(
                $stats['fetched'],
                $stats['duplicates'],
                $stats['stored'],
                $stats['errors'] ?? 0,
                ($stats['errors'] ?? 0) > 0 ? ($stats['error_details'] ?? null) : null
            );

            Log::info("Ingestion completed: {$source->name}", $stats);

        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            Log::error("Ingestion failed: {$source->name} - " . $e->getMessage());
        }

        return $log;
    }

    // ──────────────────────────────────────────────
    // RSS-based ingestion (financial, geopolitical, commodity)
    // ──────────────────────────────────────────────

    protected function ingestRssSource(NewsSource $source): array
    {
        $stats = ['fetched' => 0, 'duplicates' => 0, 'stored' => 0, 'errors' => 0, 'error_details' => null];
        $errorMessages = [];

        $xml = $this->fetchXmlWithRetry($source->url);

        if (!$xml) {
            $stats['errors'] = 1;
            $stats['error_details'] = "Failed to fetch or parse XML from {$source->url}";
            return $stats;
        }

        $items = $xml->channel->item ?? [];

        foreach ($items as $item) {
            try {
                $title = (string) $item->title;
                $url = (string) $item->link;
                $description = (string) ($item->description ?? $item->content ?? '');
                $pubDateStr = (string) $item->pubDate;

                if (empty($title) || empty($url)) {
                    $stats['errors']++;
                    $errorMessages[] = "Empty title or URL in item";
                    continue;
                }

                $stats['fetched']++;

                // Duplicate detection: URL + content hash
                if ($this->isDuplicate($url, $title)) {
                    $stats['duplicates']++;
                    continue;
                }

                $publishedAt = $pubDateStr ? Carbon::parse($pubDateStr) : now();

                RawArticle::create([
                    'news_source_id' => $source->id,
                    'title' => $title,
                    'url' => $url,
                    'body' => strip_tags($description),
                    'published_at' => $publishedAt,
                    'status' => 'pending',
                ]);

                $stats['stored']++;

            } catch (\Exception $e) {
                $stats['errors']++;
                $errorMessages[] = $e->getMessage();
                Log::warning("Error processing RSS item from {$source->name}: " . $e->getMessage());
            }
        }

        if (!empty($errorMessages)) {
            $stats['error_details'] = Str::limit(implode('; ', $errorMessages), 500);
        }

        return $stats;
    }

    // ──────────────────────────────────────────────
    // Geopolitical news ingestion (RSS + auto-classify as GeopoliticalEvent)
    // ──────────────────────────────────────────────

    protected function ingestGeopoliticalSource(NewsSource $source): array
    {
        $stats = ['fetched' => 0, 'duplicates' => 0, 'stored' => 0, 'errors' => 0, 'error_details' => null, 'geo_events_created' => 0];
        $errorMessages = [];

        $xml = $this->fetchXmlWithRetry($source->url);

        if (!$xml) {
            $stats['errors'] = 1;
            $stats['error_details'] = "Failed to fetch or parse XML from {$source->url}";
            return $stats;
        }

        $items = $xml->channel->item ?? [];

        foreach ($items as $item) {
            try {
                $title = (string) $item->title;
                $url = (string) $item->link;
                $description = (string) ($item->description ?? $item->content ?? '');
                $pubDateStr = (string) $item->pubDate;

                if (empty($title) || empty($url)) {
                    $stats['errors']++;
                    $errorMessages[] = "Empty title or URL in item";
                    continue;
                }

                $stats['fetched']++;

                // Duplicate detection: URL + content hash
                if ($this->isDuplicate($url, $title)) {
                    $stats['duplicates']++;
                    continue;
                }

                $publishedAt = $pubDateStr ? Carbon::parse($pubDateStr) : now();

                // Create RawArticle
                $rawArticle = RawArticle::create([
                    'news_source_id' => $source->id,
                    'title' => $title,
                    'url' => $url,
                    'body' => strip_tags($description),
                    'published_at' => $publishedAt,
                    'status' => 'pending',
                ]);

                $stats['stored']++;

                // Auto-classify as GeopoliticalEvent if keywords match
                $classification = GeopoliticalEventType::classifyWithConfidence($title . ' ' . strip_tags($description));

                if ($classification['type'] && $classification['confidence'] >= 30) {
                    $typeDef = GeopoliticalEventType::EVENT_TYPES[$classification['type']] ?? null;

                    // Check for duplicate geopolitical event (same raw_article_id)
                    $existingGeo = GeopoliticalEvent::where('raw_article_id', $rawArticle->id)->first();
                    if (!$existingGeo) {
                        $geoEvent = GeopoliticalEvent::create([
                            'title' => $title,
                            'description' => Str::limit(strip_tags($description), 2000),
                            'event_type' => $classification['type'],
                            'severity' => $typeDef['default_severity'] ?? 'medium',
                            'status' => 'classified',
                            'raw_article_id' => $rawArticle->id,
                            'news_source_id' => $source->id,
                            'source_url' => $url,
                            'occurred_at' => $publishedAt,
                        ]);

                        // Detect region
                        $geoEvent->detectRegion();
                        $geoEvent->save();

                        // Dispatch processing for high-severity events
                        if (in_array($geoEvent->severity, ['high', 'critical'])) {
                            ProcessGeopoliticalEventJob::dispatch($geoEvent);
                        }

                        $stats['geo_events_created']++;
                    }
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $errorMessages[] = $e->getMessage();
                Log::warning("Error processing geopolitical RSS item from {$source->name}: " . $e->getMessage());
            }
        }

        if (!empty($errorMessages)) {
            $stats['error_details'] = Str::limit(implode('; ', $errorMessages), 500);
        }

        return $stats;
    }

    // ──────────────────────────────────────────────
    // Market news ingestion (JSON/REST API based)
    // ──────────────────────────────────────────────

    protected function ingestMarketSource(NewsSource $source): array
    {
        $stats = ['fetched' => 0, 'duplicates' => 0, 'stored' => 0, 'errors' => 0, 'error_details' => null];
        $errorMessages = [];

        // Try RSS first (many market sources use RSS)
        $xml = $this->fetchXmlWithRetry($source->url);

        if ($xml && isset($xml->channel->item)) {
            return $this->ingestRssSource($source);
        }

        // Fallback: try JSON REST API
        try {
            $response = $this->fetchWithRetry($source->url);

            if (!$response->successful()) {
                $stats['errors'] = 1;
                $stats['error_details'] = "HTTP {$response->status()} from {$source->url}";
                return $stats;
            }

            $data = $response->json();
            $articles = $this->extractArticlesFromJson($data);

            foreach ($articles as $article) {
                try {
                    $title = $article['title'] ?? '';
                    $url = $article['url'] ?? $article['link'] ?? '';
                    $body = $article['body'] ?? $article['description'] ?? $article['summary'] ?? '';
                    $publishedAt = isset($article['published_at']) || isset($article['pubDate']) || isset($article['publishedAt'])
                        ? Carbon::parse($article['published_at'] ?? $article['pubDate'] ?? $article['publishedAt'])
                        : now();

                    if (empty($title) || empty($url)) {
                        $stats['errors']++;
                        continue;
                    }

                    $stats['fetched']++;

                    if ($this->isDuplicate($url, $title)) {
                        $stats['duplicates']++;
                        continue;
                    }

                    RawArticle::create([
                        'news_source_id' => $source->id,
                        'title' => $title,
                        'url' => $url,
                        'body' => strip_tags($body),
                        'published_at' => $publishedAt,
                        'status' => 'pending',
                    ]);

                    $stats['stored']++;

                } catch (\Exception $e) {
                    $stats['errors']++;
                    $errorMessages[] = $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $stats['errors'] = 1;
            $stats['error_details'] = $e->getMessage();
        }

        if (!empty($errorMessages)) {
            $stats['error_details'] = Str::limit(implode('; ', $errorMessages), 500);
        }

        return $stats;
    }

    /**
     * Extract articles array from various JSON structures.
     */
    protected function extractArticlesFromJson(array $data): array
    {
        // Common patterns: { articles: [...] }, { data: [...] }, { results: [...] }, [...]
        if (isset($data['articles']) && is_array($data['articles'])) {
            return $data['articles'];
        }
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }
        if (isset($data['results']) && is_array($data['results'])) {
            return $data['results'];
        }
        if (isset($data['items']) && is_array($data['items'])) {
            return $data['items'];
        }
        // If the root is an indexed array of articles
        if (array_is_list($data)) {
            return $data;
        }

        return [];
    }

    // ──────────────────────────────────────────────
    // Economic Calendar ingestion
    // ──────────────────────────────────────────────

    protected function ingestEconomicCalendarSource(NewsSource $source): array
    {
        $stats = ['fetched' => 0, 'duplicates' => 0, 'stored' => 0, 'errors' => 0, 'error_details' => null];
        $errorMessages = [];

        // Try to fetch from the source URL first
        $events = $this->fetchCalendarEvents($source);

        foreach ($events as $event) {
            try {
                $stats['fetched']++;

                // Normalize field names from various API formats
                $eventName = $event['event_name'] ?? $event['title'] ?? '';
                $releaseTime = $event['release_time'] ?? $event['date'] ?? null;

                if (empty($eventName) || empty($releaseTime)) {
                    $stats['errors']++;
                    $errorMessages[] = "Empty name or date in event #{$stats['fetched']}";
                    continue;
                }

                // Map importance/impact field (API may use 'impact' with capitalized values)
                $importance = strtolower($event['importance'] ?? $event['impact'] ?? 'low');
                if (!in_array($importance, ['low', 'medium', 'high'])) {
                    $importance = 'low';
                }

                // Map country (some APIs use full country name or 'All')
                $country = strtoupper($event['country'] ?? 'USD');
                if ($country === 'ALL' || empty($country)) {
                    $country = 'USD';
                }

                // Classify indicator type from event name
                $indicatorType = EconomicIndicator::classify($eventName);

                // Map importance from indicator defaults if classified
                if ($indicatorType && isset(EconomicIndicator::INDICATORS[$indicatorType])) {
                    $indicatorDef = EconomicIndicator::INDICATORS[$indicatorType];
                    if ($importance === 'low') {
                        $importance = $indicatorDef['default_importance'];
                    }
                }

                // Duplicate detection for events: same name + same release time
                if ($this->isDuplicateEvent($eventName, $releaseTime)) {
                    $stats['duplicates']++;
                    continue;
                }

                $event = EconomicEvent::create([
                    'event_name' => $eventName,
                    'indicator_type' => $indicatorType,
                    'country' => $country,
                    'actual' => $event['actual'] ?? null,
                    'forecast' => $event['forecast'] ?? null,
                    'previous' => $event['previous'] ?? null,
                    'importance' => $importance,
                    'release_time' => Carbon::parse($releaseTime),
                    'status' => 'pending',
                ]);

                // Compute surprise if we have actual + forecast
                $event->computeSurprise();
                $event->save();

                $stats['stored']++;

            } catch (\Exception $e) {
                $stats['errors']++;
                $errorMessages[] = $e->getMessage();
                Log::warning("Error processing calendar event from {$source->name}: " . $e->getMessage());
            }
        }

        if (!empty($errorMessages)) {
            $stats['error_details'] = Str::limit(implode('; ', array_slice($errorMessages, 0, 5)), 500);
        }

        return $stats;
    }

    /**
     * Fetch calendar events from source URL, or fall back to mock data.
     */
    protected function fetchCalendarEvents(NewsSource $source): array
    {
        // Try real API fetch
        try {
            $response = $this->fetchWithRetry($source->url);

            if ($response->successful()) {
                $data = $response->json();

                // Try to extract events from common JSON structures
                $events = $this->extractArticlesFromJson($data);

                if (!empty($events)) {
                    return $events;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to fetch calendar from {$source->url}: " . $e->getMessage());
        }

        // Fallback to mock data
        return $this->getMockCalendarEvents();
    }

    protected function getMockCalendarEvents(): array
    {
        return [
            [
                'event_name' => 'US Consumer Price Index (CPI) YoY',
                'country' => 'USD',
                'actual' => '3.1%',
                'forecast' => '2.9%',
                'previous' => '3.2%',
                'importance' => 'high',
                'release_time' => Carbon::now()->subMinutes(30)->toDateTimeString(),
            ],
            [
                'event_name' => 'FOMC Interest Rate Decision',
                'country' => 'USD',
                'actual' => '5.50%',
                'forecast' => '5.50%',
                'previous' => '5.50%',
                'importance' => 'high',
                'release_time' => Carbon::now()->addHours(2)->toDateTimeString(),
            ],
            [
                'event_name' => 'EU ECB Interest Rate Decision',
                'country' => 'EUR',
                'actual' => null,
                'forecast' => '4.25%',
                'previous' => '4.50%',
                'importance' => 'high',
                'release_time' => Carbon::now()->addDays(1)->toDateTimeString(),
            ],
            [
                'event_name' => 'US Non-Farm Payrolls (NFP)',
                'country' => 'USD',
                'actual' => '175K',
                'forecast' => '180K',
                'previous' => '230K',
                'importance' => 'high',
                'release_time' => Carbon::now()->subHours(5)->toDateTimeString(),
            ],
            [
                'event_name' => 'UK Gross Domestic Product (GDP) MoM',
                'country' => 'GBP',
                'actual' => '0.2%',
                'forecast' => '0.1%',
                'previous' => '-0.1%',
                'importance' => 'medium',
                'release_time' => Carbon::now()->subHours(12)->toDateTimeString(),
            ],
        ];
    }

    // ──────────────────────────────────────────────
    // Duplicate Detection
    // ──────────────────────────────────────────────

    /**
     * Check if an article is a duplicate by URL (exact) and title similarity.
     */
    protected function isDuplicate(string $url, string $title): bool
    {
        // Exact URL match
        if (RawArticle::where('url', $url)->exists()) {
            return true;
        }

        // Content-hash match: normalize the title and check for near-duplicates
        $normalizedTitle = $this->normalizeTitle($title);

        if (RawArticle::whereRaw("LOWER(REPLACE(title, ' ', '')) = ?", [str_replace(' ', '', strtolower($normalizedTitle))])->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Check if an economic event is a duplicate by name + release time.
     */
    protected function isDuplicateEvent(string $eventName, string $releaseTime): bool
    {
        return EconomicEvent::where('event_name', $eventName)
            ->where('release_time', Carbon::parse($releaseTime))
            ->exists();
    }

    /**
     * Normalize a title for fuzzy duplicate comparison.
     */
    protected function normalizeTitle(string $title): string
    {
        // Remove common prefixes, trim, collapse whitespace
        $title = preg_replace('/^(breaking|update|exclusive):\s*/i', '', $title);
        $title = preg_replace('/\s+/', ' ', trim($title));

        return $title;
    }

    // ──────────────────────────────────────────────
    // HTTP Helpers with Retry
    // ──────────────────────────────────────────────

    /**
     * Fetch URL with retry logic. Returns HTTP Response.
     */
    protected function fetchWithRetry(string $url, int $attempt = 0): \Illuminate\Http\Client\Response
    {
        try {
            return Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json, */*'])
                ->get($url);
        } catch (\Exception $e) {
            if ($attempt < $this->maxRetries) {
                Log::warning("Retrying fetch (attempt " . ($attempt + 1) . "): {$url} - " . $e->getMessage());
                sleep(2 ** $attempt); // Exponential backoff: 1s, 2s
                return $this->fetchWithRetry($url, $attempt + 1);
            }
            throw $e;
        }
    }

    /**
     * Fetch and parse XML with retry logic. Returns SimpleXMLElement or null.
     */
    protected function fetchXmlWithRetry(string $url, int $attempt = 0): ?\SimpleXMLElement
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/rss+xml, application/xml, text/xml, */*'])
                ->get($url);

            if (!$response->successful()) {
                if ($attempt < $this->maxRetries) {
                    Log::warning("Retrying XML fetch (attempt " . ($attempt + 1) . "): HTTP {$response->status()} from {$url}");
                    sleep(2 ** $attempt);
                    return $this->fetchXmlWithRetry($url, $attempt + 1);
                }
                return null;
            }

            $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);

            if (!$xml) {
                if ($attempt < $this->maxRetries) {
                    Log::warning("Retrying XML parse (attempt " . ($attempt + 1) . "): invalid XML from {$url}");
                    sleep(2 ** $attempt);
                    return $this->fetchXmlWithRetry($url, $attempt + 1);
                }
                return null;
            }

            return $xml;

        } catch (\Exception $e) {
            if ($attempt < $this->maxRetries) {
                Log::warning("Retrying XML fetch (attempt " . ($attempt + 1) . "): {$url} - " . $e->getMessage());
                sleep(2 ** $attempt);
                return $this->fetchXmlWithRetry($url, $attempt + 1);
            }
            Log::error("XML fetch failed after retries: {$url} - " . $e->getMessage());
            return null;
        }
    }

    // ──────────────────────────────────────────────
    // Legacy compatibility methods (used by DashboardController)
    // ──────────────────────────────────────────────

    /**
     * Ingest all active news sources (legacy entry point).
     * Returns count of new articles stored.
     */
    public function ingestNews(): int
    {
        $results = $this->ingestAll();
        return collect($results)->where('source_type', '!=', 'economic_calendar')->sum('stored_count');
    }

    /**
     * Ingest Economic Calendar (legacy entry point).
     * Returns count of new events stored.
     */
    public function ingestEconomicCalendar(): int
    {
        $results = $this->ingestByType('economic_calendar');
        return collect($results)->sum('stored_count');
    }
}
