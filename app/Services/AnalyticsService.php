<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleAnalytics;
use App\Models\ArticleTrafficSource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function trackListingImpressions(iterable $articles, Request $request): void
    {
        $date = Carbon::today();

        foreach ($articles as $article) {
            $this->incrementAnalytics($article, ['impressions' => 1]);
        }
    }

    public function trackArticleView(Article $article, Request $request): void
    {
        $this->incrementAnalytics($article, ['views' => 1, 'clicks' => 1]);
        $article->increment('view_count');
        $this->trackTrafficSource($article, $request, ['views' => 1, 'clicks' => 1]);
    }

    public function trackTrafficSource(Article $article, Request $request, array $increments = ['views' => 1]): void
    {
        $sourceData = $this->resolveSourceData($request);
        $date = Carbon::today();

        $trafficRow = ArticleTrafficSource::firstOrCreate([
            'article_id' => $article->id,
            'source' => $sourceData['source'],
            'medium' => $sourceData['medium'],
            'host' => $sourceData['host'],
            'date' => $date,
        ], [
            'referrer' => $sourceData['referrer'],
            'views' => 0,
            'clicks' => 0,
        ]);

        foreach ($increments as $field => $value) {
            $trafficRow->increment($field, $value);
        }
    }

    protected function resolveSourceData(Request $request): array
    {
        $utmSource = $request->query('utm_source');
        $utmMedium = $request->query('utm_medium');
        $referer = $request->headers->get('referer');
        $host = $referer ? parse_url($referer, PHP_URL_HOST) : null;

        if ($utmSource) {
            $source = $utmSource;
        } elseif ($referer) {
            $source = 'referral:' . $host;
        } else {
            $source = 'direct';
        }

        if ($utmMedium) {
            $medium = $utmMedium;
        } elseif ($referer) {
            $medium = 'referral';
        } else {
            $medium = 'direct';
        }

        return [
            'source' => $source,
            'medium' => $medium,
            'referrer' => $referer,
            'host' => $host,
        ];
    }

    protected function incrementAnalytics(Article $article, array $fields): void
    {
        $date = Carbon::today();

        $analytics = ArticleAnalytics::firstOrNew([
            'article_id' => $article->id,
            'date' => $date,
        ]);

        foreach ($fields as $field => $value) {
            $analytics->{$field} = ($analytics->{$field} ?? 0) + $value;
        }

        $analytics->save();
    }

    public function getTopArticles(int $limit = 8)
    {
        return Article::where('status', 'published')
            ->orderByDesc('view_count')
            ->take($limit)
            ->get();
    }

    public function getPopularCategories(int $limit = 8)
    {
        return DB::table('categories')
            ->join('article_category', 'categories.id', '=', 'article_category.category_id')
            ->join('articles', 'article_category.article_id', '=', 'articles.id')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(DISTINCT articles.id) as article_count'), DB::raw('SUM(articles.view_count) as total_views'))
            ->where('articles.status', 'published')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get();
    }

    public function getTrafficSources(int $days = 7)
    {
        $startDate = Carbon::today()->subDays($days - 1);

        return ArticleTrafficSource::selectRaw('source, medium, SUM(views) as views, SUM(clicks) as clicks')
            ->whereDate('date', '>=', $startDate)
            ->groupBy('source', 'medium')
            ->orderByDesc('views')
            ->get();
    }

    public function getOverallCtr(): float
    {
        $analytics = ArticleAnalytics::selectRaw('SUM(views) as views, SUM(impressions) as impressions')->first();

        if (!$analytics || $analytics->impressions === 0) {
            return 0.0;
        }

        return round(($analytics->views / max($analytics->impressions, 1)) * 100, 2);
    }
}
