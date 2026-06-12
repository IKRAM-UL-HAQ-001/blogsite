@extends('layouts.app')

@section('title', 'Category Intelligence Hub - FinIntel.AI')
@section('meta_description', 'Browse market intelligence categories and read curated financial insight reports by topic.')

@section('content')
<div class="container py-4">
    <div class="row align-items-center mb-4">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold">Market Intelligence Categories</h1>
            <p class="lead text-muted">Discover curated intelligence feeds organized by event themes, asset classes, and macro risk categories.</p>
        </div>
        <div class="col-lg-5">
            <form method="GET" action="{{ route('categories.index') }}" class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search categories" value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
            </form>
        </div>
    </div>

    @if($categories->count())
        <div class="row g-4">
            @foreach($categories as $category)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 d-flex flex-column p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary-subtle text-primary">{{ $category->articles_count }} reports</span>
                            @if($category->parent)
                                <span class="text-muted" style="font-size: 0.85rem;">Parent: {{ $category->parent->name }}</span>
                            @endif
                        </div>
                        <h2 class="h5 mb-3">
                            <a href="{{ route('categories.show', $category->slug) }}" class="text-decoration-none text-white">{{ $category->name }}</a>
                        </h2>
                        <p class="text-muted mb-4" style="min-height: 78px;">{{ $category->description ?: 'Topic collection for market alerts and news-driven intelligence.' }}</p>
                        <div class="mt-auto">
                            <a href="{{ route('categories.show', $category->slug) }}" class="btn btn-outline-primary btn-sm">View insights</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 d-flex justify-content-center">
            {{ $categories->links('pagination::bootstrap-5') }}
        </div>
    @else
        <div class="card p-5 text-center">
            <h3 class="mb-3">No categories found</h3>
            <p class="text-muted">Try adjusting your search or return to the intelligence feed for more published reports.</p>
            <a href="{{ route('categories.index') }}" class="btn btn-primary">Reset</a>
        </div>
    @endif
</div>
@endsection
