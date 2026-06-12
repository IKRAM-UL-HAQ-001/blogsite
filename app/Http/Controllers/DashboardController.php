<?php

namespace App\Http\Controllers;

use App\Models\NewsSource;
use App\Models\RawArticle;
use App\Models\EconomicEvent;
use App\Models\MarketImpact;
use App\Models\Article;
use App\Services\AnalyticsService;
use App\Services\IngestionService;
use App\Jobs\AnalyzeMarketImpactJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index()
    {
        $today = Carbon::today();
        $trafficByDay = collect(range(6, 0))->mapWithKeys(function (int $daysAgo) use ($today) {
            $date = $today->copy()->subDays($daysAgo);

            return [$date->format('M d') => 0];
        });

        $analyticsTraffic = DB::table('article_analytics')
            ->selectRaw('date, SUM(views) as views, SUM(unique_visitors) as unique_visitors')
            ->whereDate('date', '>=', $today->copy()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($analyticsTraffic as $traffic) {
            $trafficByDay[Carbon::parse($traffic->date)->format('M d')] = (int) $traffic->views;
        }

        $totalTraffic = (int) $analyticsTraffic->sum('views');
        $uniqueVisitors = (int) $analyticsTraffic->sum('unique_visitors');

        if ($totalTraffic === 0) {
            $totalTraffic = (int) Article::sum('view_count');
        }

        $stats = [
            'sources' => NewsSource::count(),
            'raw_articles' => RawArticle::count(),
            'events' => EconomicEvent::count(),
            'impacts' => MarketImpact::count(),
            'articles' => Article::count(),
            'total_articles' => Article::count(),
            'published_articles' => Article::where('status', 'published')->count(),
            'pending_articles' => Article::whereIn('status', ['draft', 'scheduled'])->count(),
            'economic_events_today' => EconomicEvent::whereDate('release_time', $today)->count(),
            'news_processed_today' => RawArticle::whereDate('updated_at', $today)
                ->where('status', '!=', 'pending')
                ->count(),
            'ai_generated_articles' => Article::whereNotNull('market_impact_id')->count(),
            'traffic_total' => $totalTraffic,
            'unique_visitors' => $uniqueVisitors,
            'traffic_today' => (int) DB::table('article_analytics')
                ->whereDate('date', $today)
                ->sum('views'),
        ];

        $recentNews = RawArticle::with('newsSource')->orderBy('created_at', 'desc')->take(6)->get();
        $recentArticles = Article::latest()->take(6)->get();
        $recentImpacts = MarketImpact::with(['rawArticle', 'economicEvent'])->orderBy('created_at', 'desc')->take(6)->get();
        $trafficOverview = [
            'labels' => $trafficByDay->keys()->values(),
            'views' => $trafficByDay->values(),
            'max' => max($trafficByDay->max(), 1),
        ];

        $topArticles = $this->analyticsService->getTopArticles(6);
        $popularCategories = $this->analyticsService->getPopularCategories(6);
        $trafficSources = $this->analyticsService->getTrafficSources(7);
        $ctrOverall = $this->analyticsService->getOverallCtr();

        return view('dashboard.index', compact('stats', 'recentNews', 'recentArticles', 'recentImpacts', 'trafficOverview', 'topArticles', 'popularCategories', 'trafficSources', 'ctrOverall'));
    }

    public function seedSources()
    {
        $sources = [
            [
                'name' => 'Yahoo Finance - Macro Economy',
                'url' => 'https://finance.yahoo.com/news/rssindex',
                'type' => 'financial',
                'is_active' => true
            ],
            [
                'name' => 'MarketWatch - Top Stories',
                'url' => 'https://feeds.content.outlook.com/rss/marketwatch/topstories', // fallback rss or general feed
                'type' => 'financial',
                'is_active' => true
            ],
            [
                'name' => 'Reuters - Geopolitics & World News',
                'url' => 'https://www.reutersagency.com/feed/',
                'type' => 'geopolitical',
                'is_active' => true
            ]
        ];

        foreach ($sources as $source) {
            NewsSource::firstOrCreate(
                ['url' => $source['url']],
                [
                    'name' => $source['name'],
                    'type' => $source['type'],
                    'is_active' => $source['is_active']
                ]
            );
        }

        return redirect()->route('admin.dashboard')->with('success', 'Default news sources seeded successfully.');
    }

    public function triggerIngest(IngestionService $ingestionService)
    {
        // Seed default sources if empty
        if (NewsSource::count() === 0) {
            $this->seedSources();
        }

        $results = $ingestionService->ingestAll();

        $totalStored = collect($results)->sum('stored_count');
        $totalDuplicates = collect($results)->sum('duplicates_skipped');
        $failedSources = collect($results)->where('status', 'failed')->count();

        $message = "Ingested {$totalStored} new items, {$totalDuplicates} duplicates skipped";
        if ($failedSources > 0) {
            $message .= ", {$failedSources} sources failed";
        }

        return redirect()->route('admin.dashboard')->with('success', $message . '.');
    }

    public function triggerAnalysis()
    {
        $pendingNews = RawArticle::where('status', 'pending')->get();
        $pendingEvents = EconomicEvent::where('status', 'pending')->get();

        $count = 0;
        foreach ($pendingNews as $news) {
            // Dispatch/Run impact job
            AnalyzeMarketImpactJob::dispatchSync($news, null);
            $count++;
        }

        foreach ($pendingEvents as $event) {
            AnalyzeMarketImpactJob::dispatchSync(null, $event);
            $count++;
        }

        return redirect()->route('admin.dashboard')->with('success', "Processed AI Market Impact analysis for {$count} items. SEO articles/images generated for high impact results.");
    }
}
