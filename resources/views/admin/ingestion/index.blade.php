@extends('layouts.app')

@section('title', 'Ingestion Engine - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Admin Module</div>
                <h1 class="h2 fw-bold text-white mb-1">News Ingestion Engine</h1>
                <p class="text-muted mb-0">Scheduled collection, duplicate detection, and raw data storage for all sources.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-self-start">
                <a href="{{ route('admin.news-sources.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-rss me-1"></i> Manage Sources
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show bg-danger-subtle text-danger border border-danger-subtle mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Summary Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value">{{ number_format($summary['total_runs']) }}</div>
                    <div class="stat-label">Total Runs</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value">{{ number_format($summary['total_stored']) }}</div>
                    <div class="stat-label">Items Stored</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value">{{ number_format($summary['total_duplicates']) }}</div>
                    <div class="stat-label">Duplicates Skipped</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-value {{ $summary['total_errors'] > 0 ? 'text-danger' : '' }}">{{ number_format($summary['total_errors']) }}</div>
                    <div class="stat-label">Failed Runs</div>
                </div>
            </div>
        </div>

        {{-- Trigger Actions --}}
        <div class="source-panel mb-4">
            <h6 class="fw-semibold text-white mb-3"><i class="bi bi-lightning-charge me-2"></i>Trigger Ingestion</h6>
            <div class="trigger-grid">
                <form method="POST" action="{{ route('admin.ingestion.trigger-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-play-circle me-1"></i> Run All (Sync)
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.ingestion.dispatch-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-collection me-1"></i> Queue All Jobs
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.ingestion.trigger-type', 'financial') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-success w-100">
                        <i class="bi bi-currency-dollar me-1"></i> Financial
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.ingestion.trigger-type', 'geopolitical') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning w-100">
                        <i class="bi bi-globe me-1"></i> Geopolitical
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.ingestion.trigger-type', 'market') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-info w-100">
                        <i class="bi bi-graph-up me-1"></i> Market
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.ingestion.trigger-type', 'economic_calendar') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light w-100">
                        <i class="bi bi-calendar-event me-1"></i> Calendar
                    </button>
                </form>
            </div>
            @if($sources->isEmpty())
                <div class="mt-3">
                    <form method="POST" action="{{ route('admin.ingestion.seed-sources') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-cloud-download me-1"></i> Seed Default Sources
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Filter --}}
        <div class="source-panel mb-4">
            <form method="GET" action="{{ route('admin.ingestion.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label for="type" class="form-label">Source Type</label>
                    <select id="type" name="type" class="form-select">
                        <option value="">All types</option>
                        <option value="economic_calendar" @selected(request('type') === 'economic_calendar')>Economic Calendar</option>
                        <option value="financial" @selected(request('type') === 'financial')>Financial News</option>
                        <option value="geopolitical" @selected(request('type') === 'geopolitical')>Geopolitical News</option>
                        <option value="market" @selected(request('type') === 'market')>Market News</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                        <option value="partial" @selected(request('status') === 'partial')>Partial</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                        <option value="running" @selected(request('status') === 'running')>Running</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.ingestion.index') }}" class="btn btn-outline-light">Reset</a>
                </div>
            </form>
        </div>

        {{-- Active Sources Quick Actions --}}
        @if($sources->isNotEmpty())
        <div class="source-panel mb-4">
            <h6 class="fw-semibold text-white mb-3"><i class="bi bi-broadcast me-2"></i>Active Sources ({{ $sources->count() }})</h6>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 source-table">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sources as $source)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-white">{{ $source->name }}</div>
                                    <div class="small text-muted">{{ $source->url }}</div>
                                </td>
                                <td><span class="type-pill">{{ $source->type_label }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('admin.ingestion.trigger-source', $source) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-play-fill me-1"></i> Ingest
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Ingestion Logs --}}
        <div class="source-panel p-0 overflow-hidden">
            <div class="p-3 border-bottom border-secondary">
                <h6 class="fw-semibold text-white mb-0"><i class="bi bi-list-task me-2"></i>Ingestion Logs</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 source-table">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Fetched</th>
                            <th class="text-end">Stored</th>
                            <th class="text-end">Dupes</th>
                            <th class="text-end">Errors</th>
                            <th>Duration</th>
                            <th>When</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-white">
                                        {{ $log->metadata['source_name'] ?? ($log->newsSource?->name ?? 'Unknown') }}
                                    </div>
                                </td>
                                <td><span class="type-pill">{{ \App\Models\NewsSource::TYPES[$log->source_type] ?? ucfirst(str_replace('_', ' ', $log->source_type)) }}</span></td>
                                <td>
                                    <span class="status-pill status-{{ $log->status }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $log->fetched_count }}</td>
                                <td class="text-end text-success fw-semibold">{{ $log->stored_count }}</td>
                                <td class="text-end text-warning">{{ $log->duplicates_skipped }}</td>
                                <td class="text-end {{ $log->error_count > 0 ? 'text-danger' : '' }}">{{ $log->error_count }}</td>
                                <td class="text-muted small">
                                    @isset($log->metadata['duration_ms'])
                                        {{ $log->metadata['duration_ms'] >= 1000 ? round($log->metadata['duration_ms'] / 1000, 1) . 's' : $log->metadata['duration_ms'] . 'ms' }}
                                    @else
                                        -
                                    @endisset
                                </td>
                                <td class="text-muted small">{{ $log->created_at->diffForHumans() }}</td>
                                <td>
                                    @if($log->error_message)
                                        <a href="{{ route('admin.ingestion.show-log', $log) }}" class="btn btn-outline-danger btn-sm" title="View Error">
                                            <i class="bi bi-bug"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="empty-state">No ingestion logs yet. Trigger an ingestion to get started.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection

@section('styles')
@include('admin.ingestion._styles')
@endsection
