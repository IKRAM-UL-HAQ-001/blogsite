@extends('layouts.app')

@section('title', 'News Source Management - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Admin Module</div>
                <h1 class="h2 fw-bold text-white mb-1">News Source Management</h1>
                <p class="text-muted mb-0">Manage economic calendar, financial, geopolitical, and commodity feeds.</p>
            </div>

            <a href="{{ route('admin.news-sources.create') }}" class="btn btn-primary align-self-start">
                <i class="bi bi-plus-circle me-1"></i> Add Source
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="source-panel mb-4">
            <form method="GET" action="{{ route('admin.news-sources.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Search</label>
                    <input id="search" name="search" type="search" class="form-control" value="{{ request('search') }}" placeholder="Name or URL">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="type" class="form-label">Type</label>
                    <select id="type" name="type" class="form-select">
                        <option value="">All types</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.news-sources.index') }}" class="btn btn-outline-light">Reset</a>
                </div>
            </form>
        </div>

        <div class="source-panel p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 source-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>URL</th>
                            <th>Status</th>
                            <th class="text-end">Articles</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sources as $source)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-white">{{ $source->name }}</div>
                                    <div class="small text-muted">Added {{ $source->created_at->diffForHumans() }}</div>
                                </td>
                                <td><span class="type-pill">{{ $source->type_label }}</span></td>
                                <td>
                                    <a href="{{ $source->url }}" target="_blank" rel="noopener" class="source-url">{{ $source->url }}</a>
                                </td>
                                <td>
                                    <span class="status-pill {{ $source->is_active ? 'status-active' : 'status-inactive' }}">
                                        {{ $source->status_label }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($source->raw_articles_count) }}</td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.news-sources.show', $source) }}" class="btn btn-outline-light btn-sm" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.news-sources.edit', $source) }}" class="btn btn-outline-primary btn-sm" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.news-sources.destroy', $source) }}" onsubmit="return confirm('Delete this news source? Raw articles will keep their records but no longer reference this source.');">
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
                                    <div class="empty-state">No news sources found.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $sources->links() }}
        </div>
    </div>
</div>
@endsection

@section('styles')
@include('admin.news-sources._styles')
@endsection
