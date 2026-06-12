<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\MarketImpact;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketAnalysisController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request): View
    {
        $recentImpacts = MarketImpact::with('article')
            ->latest('created_at')
            ->take(50)
            ->get();

        $totalImpacts = $recentImpacts->count();
        $bullishCount = $recentImpacts->where('sentiment', 'bullish')->count();
        $bearishCount = $recentImpacts->where('sentiment', 'bearish')->count();
        $neutralCount = $recentImpacts->where('sentiment', 'neutral')->count();

        $topAssets = [];
        foreach ($recentImpacts as $impact) {
            foreach ($impact->affected_assets ?? [] as $asset) {
                $topAssets[$asset] = ($topAssets[$asset] ?? 0) + 1;
            }
        }

        arsort($topAssets);
        $topAssets = array_slice($topAssets, 0, 8, true);

        $latestArticles = Article::query()
            ->where('status', 'published')
            ->with(['marketImpact', 'featuredImage'])
            ->orderBy('published_at', 'desc')
            ->take(6)
            ->get();

        $this->analyticsService->trackListingImpressions($latestArticles, $request);

        return view('pages.market-analysis', compact(
            'recentImpacts',
            'totalImpacts',
            'bullishCount',
            'bearishCount',
            'neutralCount',
            'topAssets',
            'latestArticles'
        ));
    }
}
