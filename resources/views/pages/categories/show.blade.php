@extends('layouts.app')

@section('title', $category->name . ' Intelligence Reports - FinIntel.AI')
@section('meta_description', 'Explore the latest AI-generated financial insights for the ' . $category->name . ' category.')

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Feed</a></li>
            <li class="breadcrumb-item"><a href="{{ route('categories.index') }}" class="text-decoration-none">Categories</a></li>
            <li class="breadcrumb-item active text-muted" aria-current="page">{{ $category->name }}</li>
        </ol>
    </nav>

    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold">{{ $category->name }}</h1>
            <p class="lead text-muted">{{ $category->description ?: 'Market intelligence and asset impact reports for this topic area.' }}</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Back to categories</a>
        </div>
    </div>

    @if($articles->count())
        <div class="row g-4">
            @foreach($articles as $article)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        @if($article->featuredImage && !str_contains($article->featuredImage->file_path, 'dummy'))
                            <img src="{{ asset('storage/' . $article->featuredImage->file_path) }}" class="card-img-top" alt="{{ $article->featuredImage->alt_text }}" style="height: 200px; object-fit: cover;">
                        @else
                            <div class="bg-dark d-flex align-items-center justify-content-center text-muted" style="height: 200px; border-bottom: 1px solid var(--bg-border);">
                                <i class="bi bi-bar-chart-line fs-1"></i>
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
            <h3 class="mb-3">No intelligence reports</h3>
            <p class="text-muted">This category does not have any published reports yet. Please check back after the scheduler generates new insights.</p>
        </div>
    @endif
</div>
@endsection
