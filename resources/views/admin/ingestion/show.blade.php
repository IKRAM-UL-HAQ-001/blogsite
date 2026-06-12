@extends('layouts.app')

@section('title', 'Ingestion Log Details - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Admin Module</div>
                <h1 class="h2 fw-bold text-white mb-1">Ingestion Log #{{ $ingestionLog->id }}</h1>
                <p class="text-muted mb-0">Detailed view of a single ingestion run.</p>
            </div>
            <a href="{{ route('admin.ingestion.index') }}" class="btn btn-outline-light align-self-start">
                <i class="bi bi-arrow-left me-1"></i> Back to Logs
            </a>
        </div>

        <div class="source-panel mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="detail-list">
                        <div class="detail-row">
                            <span>Source</span>
                            <strong class="text-white">{{ $ingestionLog->metadata['source_name'] ?? ($ingestionLog->newsSource?->name ?? 'Unknown') }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Source Type</span>
                            <span class="type-pill">{{ \App\Models\NewsSource::TYPES[$ingestionLog->source_type] ?? ucfirst(str_replace('_', ' ', $ingestionLog->source_type)) }}</span>
                        </div>
                        <div class="detail-row">
                            <span>Status</span>
                            <span class="status-pill status-{{ $ingestionLog->status }}">{{ ucfirst($ingestionLog->status) }}</span>
                        </div>
                        <div class="detail-row">
                            <span>Source URL</span>
                            <div class="detail-url">
                                <a href="{{ $ingestionLog->metadata['source_url'] ?? '#' }}" target="_blank">{{ $ingestionLog->metadata['source_url'] ?? '-' }}</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-list">
                        <div class="detail-row">
                            <span>Fetched</span>
                            <strong class="text-white">{{ $ingestionLog->fetched_count }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Stored</span>
                            <strong class="text-success">{{ $ingestionLog->stored_count }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Duplicates Skipped</span>
                            <strong class="text-warning">{{ $ingestionLog->duplicates_skipped }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Errors</span>
                            <strong class="{{ $ingestionLog->error_count > 0 ? 'text-danger' : 'text-white' }}">{{ $ingestionLog->error_count }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Duration</span>
                            <strong class="text-white">
                                @isset($ingestionLog->metadata['duration_ms'])
                                    {{ $ingestionLog->metadata['duration_ms'] >= 1000 ? round($ingestionLog->metadata['duration_ms'] / 1000, 1) . 's' : $ingestionLog->metadata['duration_ms'] . 'ms' }}
                                @else
                                    -
                                @endisset
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($ingestionLog->error_message)
        <div class="source-panel mb-4">
            <h6 class="fw-semibold text-danger mb-3"><i class="bi bi-bug me-2"></i>Error Details</h6>
            <div class="detail-row" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                <pre class="mb-0 text-danger small" style="white-space: pre-wrap; word-break: break-all;">{{ $ingestionLog->error_message }}</pre>
            </div>
        </div>
        @endif

        @if($ingestionLog->metadata)
        <div class="source-panel mb-4">
            <h6 class="fw-semibold text-white mb-3"><i class="bi bi-code-slash me-2"></i>Metadata</h6>
            <pre class="mb-0 text-muted small" style="white-space: pre-wrap; word-break: break-all;">{{ json_encode($ingestionLog->metadata, JSON_PRETTY_PRINT) }}</pre>
        </div>
        @endif

        <div class="source-panel">
            <h6 class="fw-semibold text-white mb-3"><i class="bi bi-clock me-2"></i>Timestamps</h6>
            <div class="detail-list">
                <div class="detail-row">
                    <span>Created</span>
                    <strong class="text-white">{{ $ingestionLog->created_at?->format('Y-m-d H:i:s') ?? '-' }}</strong>
                </div>
                <div class="detail-row">
                    <span>Started</span>
                    <strong class="text-white">{{ $ingestionLog->started_at?->format('Y-m-d H:i:s') ?? '-' }}</strong>
                </div>
                <div class="detail-row">
                    <span>Completed</span>
                    <strong class="text-white">{{ $ingestionLog->completed_at?->format('Y-m-d H:i:s') ?? '-' }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
@include('admin.ingestion._styles')
@endsection
