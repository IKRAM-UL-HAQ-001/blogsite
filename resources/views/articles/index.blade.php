@extends('layouts.app')

@section('title', 'Financial Intelligence Feed - FinIntel.AI')

@section('content')
<div class="container">
    
    <!-- Hero Header & Sentiment Index -->
    <div class="p-5 mb-4 rounded-4 card border-0 position-relative overflow-hidden" style="background: radial-gradient(circle at 100% 0%, rgba(37, 99, 235, 0.12) 0%, rgba(17, 22, 34, 0) 70%); border: 1px solid var(--bg-border) !important;">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 mb-3">System Online</span>
                <h1 class="display-5 fw-bold mb-3">AI Market Intelligence</h1>
                <p class="lead text-muted mb-0">Autonomous news aggregation, market impact synthesis, and article generation for quantitative finance.</p>
            </div>
            <div class="col-lg-5 offset-lg-1 mt-4 mt-lg-0">
                <div class="p-4 rounded-3" style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--bg-border);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-semibold">System-wide Sentiment Index</span>
                        <span class="badge badge-{{ strtolower($overallSentiment) }}">{{ $overallSentiment }}</span>
                    </div>
                    <div class="sentiment-bar mb-3 d-flex">
                        <div class="bg-success" style="width: {{ $sentimentRatio }}%;" title="Bullish: {{ $bullishCount }}"></div>
                        <div class="bg-danger" style="width: {{ 100 - $sentimentRatio }}%;" title="Bearish/Neutral: {{ $bearishCount + $neutralCount }}"></div>
                    </div>
                    <div class="d-flex justify-content-between text-xs text-muted" style="font-size: 0.8rem;">
                        <span><i class="bi bi-arrow-up-circle-fill text-success me-1"></i> Bullish ({{ $bullishCount }})</span>
                        <span>Bearish/Neutral ({{ $bearishCount + $neutralCount }}) <i class="bi bi-arrow-down-circle-fill text-danger ms-1"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="card p-3 mb-4">
        <form method="GET" action="{{ route('home') }}" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted border-secondary-subtle">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="search" class="form-control bg-transparent border-start-0 border-secondary-subtle text-white" placeholder="Search keywords, events..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="asset" class="form-select bg-transparent border-secondary-subtle text-white-50" onchange="this.form.submit()">
                    <option value="" class="bg-dark">All Asset Classes</option>
                    <option value="USD" {{ request('asset') == 'USD' ? 'selected' : '' }} class="bg-dark">USD Pairs</option>
                    <option value="EUR" {{ request('asset') == 'EUR' ? 'selected' : '' }} class="bg-dark">EUR Pairs</option>
                    <option value="XAU" {{ request('asset') == 'XAU' ? 'selected' : '' }} class="bg-dark">Gold (XAU)</option>
                    <option value="USO" {{ request('asset') == 'USO' ? 'selected' : '' }} class="bg-dark">Crude Oil (USO)</option>
                    <option value="SPX" {{ request('asset') == 'SPX' ? 'selected' : '' }} class="bg-dark">S&P 500 (SPX)</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sentiment" class="form-select bg-transparent border-secondary-subtle text-white-50" onchange="this.form.submit()">
                    <option value="" class="bg-dark">All Sentiments</option>
                    <option value="bullish" {{ request('sentiment') == 'bullish' ? 'selected' : '' }} class="bg-dark">Bullish</option>
                    <option value="bearish" {{ request('sentiment') == 'bearish' ? 'selected' : '' }} class="bg-dark">Bearish</option>
                    <option value="neutral" {{ request('sentiment') == 'neutral' ? 'selected' : '' }} class="bg-dark">Neutral</option>
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <a href="{{ route('home') }}" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Article Feed Grid -->
    @if($articles->count() > 0)
        <div class="row g-4">
            @foreach($articles as $article)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 d-flex flex-column">
                        @if($article->featuredImage && !str_contains($article->featuredImage->file_path, 'dummy'))
                            <img src="{{ asset('storage/' . $article->featuredImage->file_path) }}" class="card-img-top" alt="{{ $article->featuredImage->alt_text }}" style="height: 200px; object-fit: cover;">
                        @else
                            <div class="bg-dark d-flex align-items-center justify-content-center text-muted" style="height: 200px; border-bottom: 1px solid var(--bg-border);">
                                <i class="bi bi-image fs-1 text-secondary opacity-25"></i>
                            </div>
                        @endif
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                @if($article->marketImpact)
                                    <span class="badge badge-{{ strtolower($article->marketImpact->sentiment) }}">
                                        {{ ucfirst($article->marketImpact->sentiment) }} ({{ $article->marketImpact->score > 0 ? '+' : '' }}{{ $article->marketImpact->score }})
                                    </span>
                                    <span class="badge text-bg-secondary text-uppercase" style="font-size: 0.75rem;">
                                        {{ $article->marketImpact->impact_level }}
                                    </span>
                                @endif
                            </div>
                            
                            <h5 class="card-title mb-2">
                                <a href="{{ route('articles.show', $article->slug) }}" class="text-decoration-none text-white transition-colors">
                                    {{ $article->title }}
                                </a>
                            </h5>
                            
                            <p class="card-text text-muted mb-4" style="font-size: 0.9rem; line-height: 1.6;">
                                {{ Str::limit($article->excerpt, 110) }}
                            </p>
                            
                            <div class="mt-auto pt-3 border-top border-secondary-subtle d-flex justify-content-between align-items-center">
                                <span class="text-xs text-muted" style="font-size: 0.8rem;">
                                    <i class="bi bi-clock me-1"></i> {{ $article->published_at->diffForHumans() }}
                                </span>
                                @if($article->marketImpact && is_array($article->marketImpact->affected_assets))
                                    <div>
                                        @foreach(array_slice($article->marketImpact->affected_assets, 0, 2) as $asset)
                                            <span class="badge bg-dark text-white border border-secondary-subtle me-1" style="font-size: 0.7rem;">{{ $asset }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="mt-5 d-flex justify-content-center">
            {{ $articles->links('pagination::bootstrap-5') }}
        </div>
    @else
        <div class="card p-5 text-center my-5">
            <div class="my-4">
                <i class="bi bi-database-fill-slash text-muted fs-1"></i>
            </div>
            <h3>No Financial Insights Found</h3>
            <p class="text-muted">The system scheduler might still be processing data or the filters did not match any records.</p>
            <div class="mt-3">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm px-4 rounded-pill">
                    <i class="bi bi-gear-fill me-1"></i> Trigger Scraper
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
