<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\MarketImpact;
use App\Services\AnalyticsService;
use App\Services\HtmlSanitizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    protected AnalyticsService $analyticsService;
    protected HtmlSanitizerService $sanitizer;

    public function __construct(AnalyticsService $analyticsService, HtmlSanitizerService $sanitizer)
    {
        $this->analyticsService = $analyticsService;
        $this->sanitizer = $sanitizer;
    }

    public function index(Request $request)
    {
        $query = Article::where('status', 'published')
            ->with(['marketImpact.rawArticle.newsSource', 'featuredImage'])
            ->orderBy('published_at', 'desc');

        if ($request->filled('asset')) {
            $asset = $request->input('asset');
            $query->whereHas('marketImpact', function ($q) use ($asset) {
                $q->where('affected_assets', 'like', '%' . $asset . '%');
            });
        }

        if ($request->filled('sentiment')) {
            $sentiment = $request->input('sentiment');
            $query->whereHas('marketImpact', function ($q) use ($sentiment) {
                $q->where('sentiment', $sentiment);
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('body', 'like', '%' . $search . '%');
            });
        }

        $articles = $query->paginate(9)->withQueryString();
        $this->analyticsService->trackListingImpressions($articles, $request);

        $recentImpacts = MarketImpact::orderBy('created_at', 'desc')->take(20)->get();
        $totalImpacts = $recentImpacts->count();
        $bullishCount = $recentImpacts->where('sentiment', 'bullish')->count();
        $bearishCount = $recentImpacts->where('sentiment', 'bearish')->count();
        $neutralCount = $recentImpacts->where('sentiment', 'neutral')->count();

        $overallSentiment = 'Neutral';
        $sentimentRatio = 50;

        if ($totalImpacts > 0) {
            $sentimentRatio = round(($bullishCount / $totalImpacts) * 100);
            if ($bullishCount > $bearishCount && $bullishCount > $neutralCount) {
                $overallSentiment = 'Bullish';
            } elseif ($bearishCount > $bullishCount && $bearishCount > $neutralCount) {
                $overallSentiment = 'Bearish';
            }
        }

        return view('articles.index', compact(
            'articles',
            'overallSentiment',
            'sentimentRatio',
            'bullishCount',
            'bearishCount',
            'neutralCount'
        ));
    }

    public function show(Request $request, string $slug)
    {
        $article = Article::where('slug', $slug)
            ->with(['marketImpact.rawArticle.newsSource', 'marketImpact.economicEvent', 'featuredImage'])
            ->firstOrFail();

        $this->analyticsService->trackArticleView($article, $request);

        $safeBodyHtml = $this->sanitizer->sanitize(Str::markdown($article->body));

        return view('articles.show', compact('article', 'safeBodyHtml'));
    }
}
