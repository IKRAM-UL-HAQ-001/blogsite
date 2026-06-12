<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryPageController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request): View
    {
        $query = Category::query()
            ->withCount(['articles'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $query->paginate(12)->withQueryString();

        return view('pages.categories.index', compact('categories'));
    }

    public function show(string $slug, Request $request): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $query = $category->articles()
            ->where('status', 'published')
            ->with(['featuredImage', 'marketImpact'])
            ->orderBy('published_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $articles = $query->paginate(9)->withQueryString();
        $this->analyticsService->trackListingImpressions($articles, $request);

        return view('pages.categories.show', compact('category', 'articles'));
    }
}
