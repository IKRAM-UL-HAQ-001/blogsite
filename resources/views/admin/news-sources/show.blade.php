@extends('layouts.app')

@section('title', $newsSource->name.' - News Source - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Source Details</div>
                <h1 class="h2 fw-bold text-white mb-1">{{ $newsSource->name }}</h1>
                <p class="text-muted mb-0">{{ $newsSource->type_label }} source configuration and recent ingestion history.</p>
            </div>

            <div class="d-flex flex-wrap gap-2 align-self-start">
                <a href="{{ route('admin.news-sources.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <a href="{{ route('admin.news-sources.edit', $newsSource) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-5">
                <div class="source-panel h-100">
                    <h2 class="h5 mb-4">Source Profile</h2>
                    <div class="detail-list">
                        <div class="detail-row">
                            <span>Name</span>
                            <strong>{{ $newsSource->name }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Type</span>
                            <strong>{{ $newsSource->type_label }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Status</span>
                            <strong class="{{ $newsSource->is_active ? 'text-success' : 'text-danger' }}">{{ $newsSource->status_label }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Raw Articles</span>
                            <strong>{{ number_format($newsSource->raw_articles_count) }}</strong>
                        </div>
                        <div class="detail-row detail-url">
                            <span>URL</span>
                            <a href="{{ $newsSource->url }}" target="_blank" rel="noopener">{{ $newsSource->url }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="source-panel h-100">
                    <h2 class="h5 mb-4">Recent Raw Articles</h2>
                    <div class="activity-list">
                        @forelse($recentArticles as $article)
                            <div class="activity-item">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <div class="fw-semibold text-white text-truncate">{{ $article->title }}</div>
                                    <span class="status-pill status-{{ $article->status }}">{{ ucfirst($article->status) }}</span>
                                </div>
                                <div class="small text-muted">{{ $article->published_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="empty-state">No raw articles have been ingested from this source.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
@include('admin.news-sources._styles')
@endsection
