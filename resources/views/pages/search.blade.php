@extends('layouts.app')

@section('title', 'Search Market Intelligence - FinIntel.AI')
@section('meta_description', 'Search AI-generated market analysis, economic events, and news-driven intelligence reports.')

@section('content')
<div class="container py-4">
    <div class="row align-items-center mb-4">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold">Search Intelligence</h1>
            <p class="lead text-muted">Query the AI-driven feed for keywords, asset classes, and market event signals.</p>
        </div>
        <div class="col-lg-5">
            <form method="GET" action="{{ route('search.index') }}" class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search reports, assets, or keywords" value="{{ $search }}">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>

    @if($search->isNotEmpty())
        <div class="mb-4 text-muted">Showing results for <strong>"{{ $search }}"</strong>.</div>
    @endif

    @if($articles->count())
        <div class="row g-4">
            @foreach($articles as $article)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        @if($article->featuredImage && !str_contains($article->featuredImage->file_path, 'dummy'))
                            <img src="{{ asset('storage/' . $article->featuredImage->file_path) }}" class="card-img-top" alt="{{ $article->featuredImage->alt_text }}" style="height: 200px; object-fit: cover;">
                        @else
                            <div class="bg-dark d-flex align-items-center justify-content-center text-muted" style="height: 200px; border-bottom: 1px solid var(--bg-border);">
                                <i class="bi bi-search fs-1"></i>
                            </div>
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-2"><a href="{{ route('articles.show', $article->slug) }}" class="text-decoration-none text-white">{{ $article->title }}</a></h5>
                            <p class="text-muted mb-3">{{ Str::limit($article->excerpt, 100) }}</p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="text-xs text-muted" style="font-size: 0.85rem;">{{ $article->published_at->diffForHumans() }}</span>
                                @if($article->marketImpact)
                                    <span class="badge badge-{{ strtolower($article->marketImpact->sentiment) }} text-uppercase" style="font-size: 0.75rem;">{{ $article->marketImpact->sentiment }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 d-flex justify-content-center">
            {{ $articles->links('pagination::bootstrap-5') }}
        </div>
    @else
        <div class="card p-5 text-center">
            <h3 class="mb-3">No search results</h3>
            <p class="text-muted">Try a broader query or return to the intelligence feed to explore the latest reports.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">Go to feed</a>
        </div>
    @endif
</div>
@endsection
