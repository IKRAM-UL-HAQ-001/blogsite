@extends('layouts.app')

@section('title', $category->name.' - Category - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="category-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Category Details</div>
                <h1 class="h2 fw-bold text-white mb-1">{{ $category->name }}</h1>
                <p class="text-muted mb-0">{{ $category->description ?: 'Finance blog taxonomy profile.' }}</p>
            </div>

            <div class="d-flex flex-wrap gap-2 align-self-start">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-5">
                <div class="category-panel h-100">
                    <h2 class="h5 mb-4">Category Profile</h2>
                    <div class="detail-list">
                        <div class="detail-row">
                            <span>Name</span>
                            <strong>{{ $category->name }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Slug</span>
                            <strong>{{ $category->slug }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Parent</span>
                            <strong>{{ $category->parent?->name ?? 'Root category' }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Articles</span>
                            <strong>{{ number_format($category->articles_count) }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Children</span>
                            <strong>{{ number_format($category->children_count) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="category-panel h-100">
                    <h2 class="h5 mb-4">Recent Articles</h2>
                    <div class="activity-list">
                        @forelse($recentArticles as $article)
                            <div class="activity-item">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <div class="fw-semibold text-white text-truncate">{{ $article->title }}</div>
                                    <span class="category-pill">{{ ucfirst($article->status) }}</span>
                                </div>
                                <div class="small text-muted">{{ number_format($article->view_count) }} views · {{ $article->created_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="empty-state">No articles are assigned to this category yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
@include('admin.categories._styles')
@endsection
