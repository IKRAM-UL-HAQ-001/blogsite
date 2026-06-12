<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request): View
    {
        $search = $request->string('search');

        $query = Article::query()
            ->where('status', 'published')
            ->with(['featuredImage', 'marketImpact'])
            ->orderBy('published_at', 'desc');

        if ($search->isNotEmpty()) {
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $articles = $query->paginate(9)->withQueryString();
        $this->analyticsService->trackListingImpressions($articles, $request);

        return view('pages.search', compact('articles', 'search'));
    }
}
