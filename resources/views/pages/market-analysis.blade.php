@extends('layouts.app')

@section('title', 'Market Analysis & Signal Flow - FinIntel.AI')
@section('meta_description', 'Explore real-time signal flow, sentiment trends, and AI-driven market analysis for major asset classes.')

@section('content')
@php
    $bullishPct = $totalImpacts ? round(($bullishCount / $totalImpacts) * 100) : 0;
    $bearishPct = $totalImpacts ? round(($bearishCount / $totalImpacts) * 100) : 0;
    $neutralPct = $totalImpacts ? round(($neutralCount / $totalImpacts) * 100) : 0;
    $progressClass = function ($pct) {
        if ($pct >= 90) {
            return 'w-100';
        }

        if ($pct >= 75) {
            return 'w-75';
        }

        if ($pct >= 50) {
            return 'w-50';
        }

        if ($pct >= 25) {
            return 'w-25';
        }

        return 'w-0';
    };
@endphp
<div class="container py-4">
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h1 class="display-5 fw-bold">Market Analysis & Signal Flow</h1>
            <p class="lead text-muted">A synthesized view of market sentiment, event impact, and asset-level trends from the intelligence pipeline.</p>
        </div>
        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
            <a href="{{ route('search.index') }}" class="btn btn-outline-primary">Search reports</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h5 class="text-muted text-uppercase mb-3">Published Reports</h5>
                <div class="display-6 fw-bold">{{ $latestArticles->count() }}</div>
                <p class="text-muted mt-2">Most recent published intelligence reports available for review.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h5 class="text-muted text-uppercase mb-3">Signal Events</h5>
                <div class="display-6 fw-bold">{{ $totalImpacts }}</div>
                <p class="text-muted mt-2">Recent AI-scored market impact events in the pipeline.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h5 class="text-muted text-uppercase mb-3">Sentiment Balance</h5>
                <div class="mb-3">
                    <div class="d-flex justify-content-between text-muted mb-1"><span>Bullish</span><span>{{ $bullishCount }}</span></div>
                    <div class="progress" style="height: 8px;"><div class="progress-bar bg-success {{ $progressClass($bullishPct) }}" role="progressbar" aria-valuenow="{{ $bullishPct }}" aria-valuemin="0" aria-valuemax="100">{{ $bullishPct }}%</div></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between text-muted mb-1"><span>Bearish</span><span>{{ $bearishCount }}</span></div>
                    <div class="progress" style="height: 8px;"><div class="progress-bar bg-danger {{ $progressClass($bearishPct) }}" role="progressbar" aria-valuenow="{{ $bearishPct }}" aria-valuemin="0" aria-valuemax="100">{{ $bearishPct }}%</div></div>
                </div>
                <div>
                    <div class="d-flex justify-content-between text-muted mb-1"><span>Neutral</span><span>{{ $neutralCount }}</span></div>
                    <div class="progress" style="height: 8px;"><div class="progress-bar bg-secondary {{ $progressClass($neutralPct) }}" role="progressbar" aria-valuenow="{{ $neutralPct }}" aria-valuemin="0" aria-valuemax="100">{{ $neutralPct }}%</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card p-4 h-100">
                <h5 class="mb-3">Top Impacted Assets</h5>
                @if(count($topAssets))
                    <div class="list-group list-group-flush">
                        @foreach($topAssets as $asset => $count)
                            <div class="list-group-item bg-transparent border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                                <span>{{ $asset }}</span>
                                <span class="badge bg-primary-subtle text-primary">{{ $count }} signals</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">No asset signals are available yet.</p>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4 h-100">
                <h5 class="mb-3">Latest Intelligence Alerts</h5>
                @if($latestArticles->count())
                    <ul class="list-group list-group-flush">
                        @foreach($latestArticles as $article)
                            <li class="list-group-item bg-transparent border-0 px-0 py-3">
                                <a href="{{ route('articles.show', $article->slug) }}" class="text-decoration-none text-white">{{ $article->title }}</a>
                                <div class="text-muted small">{{ $article->published_at->diffForHumans() }}</div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">No published articles are available yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="card p-4">
        <h4 class="mb-3">AI Market Intelligence Notes</h4>
        <p class="text-muted mb-0">This page aggregates recent impact signals, highlighting how the FinIntel.AI pipeline transforms raw news and macro data into concise market commentary. Use the search tools or category hub to drill into specific sectors, assets, and event-driven research.</p>
    </div>
</div>
@endsection
