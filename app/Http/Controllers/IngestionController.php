<?php

namespace App\Http\Controllers;

use App\Models\IngestionLog;
use App\Models\NewsSource;
use App\Models\RawArticle;
use App\Services\IngestionService;
use App\Jobs\FetchNewsJob;
use App\Jobs\FetchCalendarJob;
use App\Jobs\FetchFinancialNewsJob;
use App\Jobs\FetchGeopoliticalNewsJob;
use App\Jobs\FetchMarketNewsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IngestionController extends Controller
{
    public function __construct(protected IngestionService $ingestionService) {}

    /**
     * Display the ingestion dashboard with logs.
     */
    public function index(Request $request)
    {
        $query = IngestionLog::with('newsSource');

        if ($request->filled('type')) {
            $query->where('source_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);
        $sources = NewsSource::where('is_active', true)->get();

        $summary = [
            'total_runs' => IngestionLog::count(),
            'total_stored' => IngestionLog::sum('stored_count'),
            'total_duplicates' => IngestionLog::sum('duplicates_skipped'),
            'total_errors' => IngestionLog::where('status', 'failed')->count(),
            'last_run' => IngestionLog::latest('created_at')->value('created_at'),
        ];

        return view('admin.ingestion.index', compact('logs', 'sources', 'summary'));
    }

    /**
     * Trigger ingestion for all sources synchronously.
     */
    public function triggerAll()
    {
        try {
            $results = $this->ingestionService->ingestAll();

            $totalStored = collect($results)->sum('stored_count');
            $totalDuplicates = collect($results)->sum('duplicates_skipped');
            $failedSources = collect($results)->where('status', 'failed')->count();

            $message = "Ingestion complete: {$totalStored} new items stored, {$totalDuplicates} duplicates skipped";

            if ($failedSources > 0) {
                $message .= ", {$failedSources} sources failed";
            }

            return redirect()->route('admin.ingestion.index')->with('success', $message);

        } catch (\Exception $e) {
            Log::error("Ingestion trigger failed: " . $e->getMessage());
            return redirect()->route('admin.ingestion.index')->with('error', 'Ingestion failed: ' . $e->getMessage());
        }
    }

    /**
     * Trigger ingestion for a specific source type (dispatched to queue).
     */
    public function triggerByType(string $type)
    {
        $validTypes = ['financial', 'geopolitical', 'market', 'economic_calendar'];

        if (!in_array($type, $validTypes)) {
            return redirect()->route('admin.ingestion.index')->with('error', "Invalid source type: {$type}");
        }

        $job = match ($type) {
            'financial' => new FetchFinancialNewsJob(),
            'geopolitical' => new FetchGeopoliticalNewsJob(),
            'market' => new FetchMarketNewsJob(),
            'economic_calendar' => new FetchCalendarJob(),
        };

        dispatch($job);

        $label = NewsSource::TYPES[$type] ?? $type;

        return redirect()->route('admin.ingestion.index')
            ->with('success', "{$label} ingestion dispatched to queue.");
    }

    /**
     * Trigger ingestion for a single source.
     */
    public function triggerSource(NewsSource $newsSource)
    {
        $log = $this->ingestionService->ingestSource($newsSource);

        $status = $log->status === 'failed' ? 'error' : 'success';
        $message = $log->status === 'failed'
            ? "Ingestion failed for {$newsSource->name}: {$log->error_message}"
            : "Ingested {$newsSource->name}: {$log->stored_count} stored, {$log->duplicates_skipped} duplicates";

        return redirect()->route('admin.ingestion.index')->with($status, $message);
    }

    /**
     * Show ingestion log details.
     */
    public function showLog(IngestionLog $ingestionLog)
    {
        $ingestionLog->load('newsSource');

        return view('admin.ingestion.show', compact('ingestionLog'));
    }

    /**
     * Dispatch all ingestion jobs to queue.
     */
    public function dispatchAllToQueue()
    {
        dispatch(new FetchFinancialNewsJob());
        dispatch(new FetchGeopoliticalNewsJob());
        dispatch(new FetchMarketNewsJob());
        dispatch(new FetchCalendarJob());

        return redirect()->route('admin.ingestion.index')
            ->with('success', 'All ingestion jobs dispatched to queue.');
    }

    /**
     * Seed default news sources.
     */
    public function seedSources()
    {
        $sources = [
            [
                'name' => 'Yahoo Finance - Macro Economy',
                'url' => 'https://finance.yahoo.com/news/rssindex',
                'type' => 'financial',
                'is_active' => true,
            ],
            [
                'name' => 'MarketWatch - Top Stories',
                'url' => 'https://feeds.content.outlook.com/rss/marketwatch/topstories',
                'type' => 'financial',
                'is_active' => true,
            ],
            [
                'name' => 'Reuters - Geopolitics & World News',
                'url' => 'https://www.reutersagency.com/feed/',
                'type' => 'geopolitical',
                'is_active' => true,
            ],
            [
                'name' => 'Investing.com - Market News',
                'url' => 'https://www.investing.com/rss/news_301.rss',
                'type' => 'market',
                'is_active' => true,
            ],
            [
                'name' => 'ForexLive - Currency Analysis',
                'url' => 'https://www.forexlive.com/feed',
                'type' => 'market',
                'is_active' => true,
            ],
            [
                'name' => 'Economic Calendar - ForexFactory',
                'url' => 'https://nfs.faireconomy.media/ff_calendar_thisweek.json',
                'type' => 'economic_calendar',
                'is_active' => true,
            ],
        ];

        $created = 0;
        foreach ($sources as $source) {
            $result = NewsSource::firstOrCreate(
                ['url' => $source['url']],
                [
                    'name' => $source['name'],
                    'type' => $source['type'],
                    'is_active' => $source['is_active'],
                ]
            );
            if ($result->wasRecentlyCreated) {
                $created++;
            }
        }

        return redirect()->route('admin.ingestion.index')
            ->with('success', "News sources seeded. {$created} new sources added.");
    }
}
