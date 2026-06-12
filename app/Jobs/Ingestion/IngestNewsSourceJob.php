<?php

namespace App\Jobs\Ingestion;

use App\Contracts\NewsProvider;
use App\Models\NewsSource;
use App\Models\PipelineRun;
use App\Models\RawArticle;
use App\Services\IngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Ingests a single NewsSource. One job per source, dispatched by
 * DispatchSourceIngestionJob every 10 minutes.
 *
 * ShouldBeUnique with uniqueFor = 600 (10 min) ensures that if a source
 * is slow we don't queue a second copy before the first finishes.
 */
final class IngestNewsSourceJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 3;
    public int $timeout   = 120;
    public int $uniqueFor = 600;

    public function __construct(public readonly int $sourceId)
    {
        $this->onQueue('ingestion');
    }

    public function uniqueId(): string
    {
        return "news-source:{$this->sourceId}";
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(IngestionService $ingestionService): void
    {
        $source = NewsSource::find($this->sourceId);

        if (! $source || ! $source->is_active) {
            return;
        }

        $run = PipelineRun::start("ingest:{$source->type}", [
            'source_id'   => $source->id,
            'source_name' => $source->name,
        ]);

        try {
            // If the source has a dedicated provider class, use the new provider system.
            if ($source->provider_class && class_exists($source->provider_class)) {
                [$received, $stored, $duplicates] = $this->ingestViaProvider($source);
                $source->markFetched();
                $run->complete($received, $stored, 0, ['duplicates' => $duplicates]);

                Log::info("IngestNewsSourceJob ({$source->name}): stored {$stored}, duplicates {$duplicates}.");
                return;
            }

            // Fallback: delegate to the existing IngestionService (handles RSS, geopolitical, calendar, etc.)
            $log = $ingestionService->ingestSource($source);
            $source->markFetched();

            $run->complete(
                $log->fetched_count  ?? 0,
                $log->stored_count   ?? 0,
                $log->error_count    ?? 0,
            );

            $storedCount = $log->stored_count ?? 0;
            Log::info("IngestNewsSourceJob ({$source->name}): stored {$storedCount}.");

        } catch (\Throwable $e) {
            $source->markFailed($e->getMessage());
            $run->fail($e->getMessage());
            Log::error("IngestNewsSourceJob failed for source #{$this->sourceId}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Use the source's assigned NewsProvider implementation.
     * Returns [items_received, items_stored, duplicates_skipped].
     *
     * @return array{int, int, int}
     */
    private function ingestViaProvider(NewsSource $source): array
    {
        $config   = $source->configuration_json ?? [];
        $since    = $source->last_fetched_at ?? now()->subHours(6);

        /** @var NewsProvider $provider */
        $provider = new ($source->provider_class)($config);

        $received   = 0;
        $stored     = 0;
        $duplicates = 0;

        foreach ($provider->fetch($since) as $item) {
            $received++;

            $hash = $item->contentHash();

            // Exact duplicate: URL or content hash
            if (
                RawArticle::where('url', $item->url)->exists() ||
                RawArticle::where('content_hash', $hash)->exists()
            ) {
                $duplicates++;
                continue;
            }

            RawArticle::create([
                'news_source_id'   => $source->id,
                'external_id'      => $item->externalId,
                'title'            => $item->title,
                'url'              => $item->url,
                'content_hash'     => $hash,
                'body'             => $item->body ?? $item->summary ?? '',
                'summary'          => $item->summary,
                'author'           => $item->author,
                'language'         => $item->language,
                'published_at'     => $item->publishedAt,
                'fetched_at'       => now(),
                'status'           => 'pending',
                'raw_payload_json' => $item->metadata ?: null,
            ]);

            $stored++;
        }

        return [$received, $stored, $duplicates];
    }

    public function failed(\Throwable $e): void
    {
        $source = NewsSource::find($this->sourceId);
        $source?->markFailed($e->getMessage());
        Log::error("IngestNewsSourceJob permanently failed for source #{$this->sourceId}: {$e->getMessage()}");
    }
}
