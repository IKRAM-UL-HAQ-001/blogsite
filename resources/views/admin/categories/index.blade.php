@extends('layouts.app')

@section('title', 'Finance Blog Categories - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="category-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Admin Module</div>
                <h1 class="h2 fw-bold text-white mb-1">Finance Blog Categories</h1>
                <p class="text-muted mb-0">Manage topic taxonomy for Forex, Stocks, Gold, Crypto, Oil, central banks, economic data, and geopolitics.</p>
            </div>

            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary align-self-start">
                <i class="bi bi-plus-circle me-1"></i> Add Category
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="category-panel mb-4">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Search</label>
                    <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" placeholder="Name, slug, or description">
                </div>
                <div class="col-md-5 col-lg-4">
                    <label for="parent" class="form-label">Parent</label>
                    <select id="parent" name="parent" class="form-select">
                        <option value="">All categories</option>
                        <option value="root" @selected(request('parent') === 'root')>Root categories</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) request('parent') === (string) $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-light">Reset</a>
                </div>
            </form>
        </div>

        <div class="category-panel p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 category-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Parent</th>
                            <th class="text-end">Articles</th>
                            <th class="text-end">Children</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-white">{{ $category->name }}</div>
                                    <div class="small text-muted text-truncate" style="max-width: 360px;">{{ $category->description ?: 'No description' }}</div>
                                </td>
                                <td><span class="category-pill">{{ $category->slug }}</span></td>
                                <td>
                                    @if($category->parent)
                                        <span class="category-pill">{{ $category->parent->name }}</span>
                                    @else
                                        <span class="category-pill muted-pill">Root</span>
                                    @endif
                                </td>
                                <td class="text-end"><span class="count-pill">{{ number_format($category->articles_count) }}</span></td>
                                <td class="text-end">{{ number_format($category->children_count) }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.categories.show', $category) }}" class="btn btn-outline-light btn-sm" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-primary btn-sm" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category? It will be detached from articles and child categories will become root categories.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">No categories found.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection

@section('styles')
@include('admin.categories._styles')
@endsection
