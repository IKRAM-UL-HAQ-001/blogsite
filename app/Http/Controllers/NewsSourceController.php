<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsSourceRequest;
use App\Models\NewsSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsSourceController extends Controller
{
    public function index(Request $request): View
    {
        $query = NewsSource::query()->withCount('rawArticles')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status') === 'active');
        }

        if ($request->filled('search')) {
            $search = $request->string('search');

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $sources = $query->paginate(12)->withQueryString();
        $types = NewsSource::TYPES;

        return view('admin.news-sources.index', compact('sources', 'types'));
    }

    public function create(): View
    {
        return view('admin.news-sources.create', [
            'source' => new NewsSource(['is_active' => true]),
            'types' => NewsSource::TYPES,
        ]);
    }

    public function store(NewsSourceRequest $request): RedirectResponse
    {
        NewsSource::query()->create($request->validated());

        return redirect()
            ->route('admin.news-sources.index')
            ->with('success', 'News source created successfully.');
    }

    public function show(NewsSource $newsSource): View
    {
        $newsSource->loadCount('rawArticles');

        $recentArticles = $newsSource->rawArticles()
            ->latest()
            ->take(8)
            ->get();

        return view('admin.news-sources.show', compact('newsSource', 'recentArticles'));
    }

    public function edit(NewsSource $newsSource): View
    {
        return view('admin.news-sources.edit', [
            'source' => $newsSource,
            'types' => NewsSource::TYPES,
        ]);
    }

    public function update(NewsSourceRequest $request, NewsSource $newsSource): RedirectResponse
    {
        $newsSource->update($request->validated());

        return redirect()
            ->route('admin.news-sources.index')
            ->with('success', 'News source updated successfully.');
    }

    public function destroy(NewsSource $newsSource): RedirectResponse
    {
        $newsSource->delete();

        return redirect()
            ->route('admin.news-sources.index')
            ->with('success', 'News source deleted successfully.');
    }
}
