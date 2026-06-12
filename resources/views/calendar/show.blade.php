@extends('layouts.app')

@section('title', 'Economic Event Details - FinIntel.AI')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 fw-bold text-white mb-1">{{ $event->event_name }}</h1>
            <p class="text-muted mb-0">
                {{ $event->release_time->format('F j, Y H:i') }} UTC
                &middot; {{ $event->country }}
                @if($event->indicator_type)
                    &middot; <span class="badge rounded-pill" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3);">{{ strtoupper($event->indicator_type) }}</span>
                @endif
            </p>
        </div>
        <a href="{{ route('calendar.index') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Calendar
        </a>
    </div>

    <div class="row g-4">
        <!-- Main Data Card -->
        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="fw-semibold text-white mb-3">Release Data</h5>
                <div class="row g-3">
                    <div class="col-4">
                        <div class="text-muted small text-uppercase mb-1">Actual</div>
                        <div class="h4 fw-bold text-white">{{ $event->actual ?? 'Pending' }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small text-uppercase mb-1">Forecast</div>
                        <div class="h4 text-white-50">{{ $event->forecast ?? '—' }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small text-uppercase mb-1">Previous</div>
                        <div class="h4 text-white-50">{{ $event->previous ?? '—' }}</div>
                    </div>
                </div>

                @if($event->surprise !== null)
                <hr class="border-secondary my-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small text-uppercase">Surprise</span>
                    @if($event->surprise_direction === 'beat')
                        <span class="badge text-bg-success fs-6"><i class="bi bi-arrow-up-short"></i> Beat +{{ number_format($event->surprise, 2) }}</span>
                    @elseif($event->surprise_direction === 'miss')
                        <span class="badge text-bg-danger fs-6"><i class="bi bi-arrow-down-short"></i> Miss {{ number_format($event->surprise, 2) }}</span>
                    @else
                        <span class="badge text-bg-secondary fs-6">In Line</span>
                    @endif
                </div>
                @endif

                <hr class="border-secondary my-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small text-uppercase">Importance</span>
                    @if($event->importance === 'high')
                        <span class="badge text-bg-danger">High Impact</span>
                    @elseif($event->importance === 'medium')
                        <span class="badge text-bg-warning">Medium Impact</span>
                    @else
                        <span class="badge text-bg-secondary">Low Impact</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- AI Analysis Card -->
        <div class="col-md-6">
            <div class="card p-4">
                <h5 class="fw-semibold text-white mb-3"><i class="bi bi-cpu me-2"></i>AI Market Impact</h5>
                @if($event->marketImpact)
                    <div class="mb-3">
                        <span class="badge badge-{{ strtolower($event->marketImpact->sentiment) }} fs-6">
                            {{ ucfirst($event->marketImpact->sentiment) }} (Score: {{ $event->marketImpact->score }})
                        </span>
                        <span class="badge {{ $event->marketImpact->impact_level === 'high' ? 'text-bg-danger' : ($event->marketImpact->impact_level === 'medium' ? 'text-bg-warning' : 'text-bg-secondary') }} ms-2">
                            {{ ucfirst($event->marketImpact->impact_level) }} Impact
                        </span>
                    </div>
                    <p class="text-white-50 small mb-3">{{ $event->marketImpact->market_summary }}</p>
                    <div class="mb-2">
                        <span class="text-muted small">Affected Assets:</span>
                        @foreach($event->marketImpact->affected_assets ?? [] as $asset)
                            <span class="badge bg-dark border border-secondary me-1">{{ $asset }}</span>
                        @endforeach
                    </div>
                    @if($event->marketImpact->article)
                        <a href="{{ route('articles.show', $event->marketImpact->article->slug) }}" class="btn btn-primary btn-sm mt-3">
                            <i class="bi bi-file-earmark-text me-1"></i> Read Full Report
                        </a>
                    @endif
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-cpu fs-1 d-block mb-2"></i>
                        @if($event->status === 'pending')
                            AI analysis is queued for processing.
                        @else
                            No AI analysis available for this event.
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Previous Release Comparison -->
    @if($previousRelease)
    <div class="card p-4 mt-4">
        <h5 class="fw-semibold text-white mb-3"><i class="bi bi-clock-history me-2"></i>Previous Release</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">Date</div>
                <div class="text-white">{{ $previousRelease->release_time->format('M d, Y H:i') }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Actual</div>
                <div class="text-white fw-bold">{{ $previousRelease->actual ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Forecast</div>
                <div class="text-white-50">{{ $previousRelease->forecast ?? '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">Previous</div>
                <div class="text-white-50">{{ $previousRelease->previous ?? '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Surprise</div>
                @if($previousRelease->surprise_direction === 'beat')
                    <span class="text-success fw-bold"><i class="bi bi-arrow-up-short"></i>{{ number_format($previousRelease->surprise, 2) }}</span>
                @elseif($previousRelease->surprise_direction === 'miss')
                    <span class="text-danger fw-bold"><i class="bi bi-arrow-down-short"></i>{{ number_format($previousRelease->surprise, 2) }}</span>
                @else
                    <span class="text-secondary">In Line</span>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
