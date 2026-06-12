@extends('layouts.app')

@section('title', $event->title . ' - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        {{-- Back link --}}
        <a href="{{ route('geopolitical.index') }}" class="text-muted small text-decoration-none mb-3 d-inline-block">
            <i class="bi bi-arrow-left me-1"></i> Back to Geopolitical Monitor
        </a>

        {{-- Header --}}
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-white mb-2">{{ $event->title }}</h1>
                <div class="d-flex gap-2 flex-wrap">
                    @if($event->event_type)
                        <span class="badge rounded-pill" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3);">
                            {{ $event->event_type_label }}
                        </span>
                    @endif
                    <span class="badge bg-{{ $event->severity_badge }} {{ $event->severity === 'critical' ? 'text-white' : '' }}">
                        {{ ucfirst($event->severity) }} Severity
                    </span>
                    <span class="badge bg-dark border border-secondary">{{ $event->region_label }}</span>
                    @if($event->primary_country)
                        <span class="badge bg-dark border border-info text-info">{{ $event->primary_country }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Main Content --}}
            <div class="col-lg-8">
                {{-- Description --}}
                @if($event->description)
                <div class="card p-4 mb-4">
                    <h5 class="fw-semibold text-white mb-3"><i class="bi bi-file-text me-2"></i>Event Description</h5>
                    <p class="text-white-70 mb-0" style="line-height:1.7">{{ $event->description }}</p>
                </div>
                @endif

                {{-- AI Geopolitical Analysis --}}
                @if($event->ai_geopolitical_analysis)
                <div class="card p-4 mb-4 border-primary">
                    <h5 class="fw-semibold text-white mb-3"><i class="bi bi-robot me-2"></i>AI Geopolitical Analysis</h5>
                    <p class="text-white-80 mb-0" style="line-height:1.7">{{ $event->ai_geopolitical_analysis }}</p>
                </div>
                @endif

                {{-- AI Market Summary --}}
                @if($event->ai_market_summary)
                <div class="card p-4 mb-4">
                    <h5 class="fw-semibold text-white mb-3"><i class="bi bi-graph-up me-2"></i>Market Impact Summary</h5>
                    <p class="text-white-70 mb-0">{{ $event->ai_market_summary }}</p>
                </div>
                @endif

                {{-- Risk Factors --}}
                @if($event->ai_risk_factors)
                <div class="card p-4 mb-4">
                    <h5 class="fw-semibold text-white mb-3"><i class="bi bi-exclamation-diamond me-2"></i>Risk Factors</h5>
                    <ul class="list-unstyled mb-0">
                        @foreach($event->ai_risk_factors as $factor)
                            <li class="d-flex align-items-start mb-2">
                                <i class="bi bi-chevron-right text-warning me-2 mt-1" style="font-size:0.7rem"></i>
                                <span class="text-white-70">{{ $factor }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Timeline Projection --}}
                @if($event->ai_timeline_projection)
                <div class="card p-4 mb-4">
                    <h5 class="fw-semibold text-white mb-3"><i class="bi bi-clock-history me-2"></i>Timeline Projection</h5>
                    <div class="row g-3">
                        @foreach(['short_term' => 'Short Term (0-2 weeks)', 'medium_term' => 'Medium Term (1-3 months)', 'long_term' => 'Long Term (3-12 months)'] as $key => $label)
                            @if(isset($event->ai_timeline_projection[$key]))
                            <div class="col-md-4">
                                <div class="p-3 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1)">
                                    <div class="text-muted small text-uppercase mb-1">{{ $label }}</div>
                                    <p class="text-white-70 small mb-0">{{ $event->ai_timeline_projection[$key] }}</p>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Historical Parallels --}}
                @if($event->ai_historical_parallels)
                <div class="card p-4 mb-4">
                    <h5 class="fw-semibold text-white mb-3"><i class="bi bi-book me-2"></i>Historical Parallels</h5>
                    @foreach($event->ai_historical_parallels as $parallel)
                        <div class="d-flex gap-3 mb-3 p-2 rounded" style="background: rgba(255,255,255,0.03)">
                            <div class="text-center" style="min-width:60px">
                                <div class="h5 fw-bold text-primary mb-0">{{ $parallel['year'] ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-white fw-semibold small">{{ $parallel['event'] ?? 'Unknown' }}</div>
                                <div class="text-muted small">{{ $parallel['outcome'] ?? '' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Event Details --}}
                <div class="card p-4 mb-4">
                    <h6 class="fw-semibold text-white mb-3"><i class="bi bi-info-circle me-2"></i>Event Details</h6>
                    <table class="table table-dark table-sm mb-0 border-0">
                        <tr><td class="text-muted ps-0 border-0">Type</td><td class="border-0 fw-semibold">{{ $event->event_type_label }}</td></tr>
                        <tr><td class="text-muted ps-0">Category</td><td class="fw-semibold">{{ $event->category_label }}</td></tr>
                        <tr><td class="text-muted ps-0">Severity</td><td><span class="badge bg-{{ $event->severity_badge }}">{{ ucfirst($event->severity) }}</span></td></tr>
                        <tr><td class="text-muted ps-0">Region</td><td>{{ $event->region_label }}</td></tr>
                        <tr><td class="text-muted ps-0">Escalation</td><td>{{ $event->escalation_label }}</td></tr>
                        <tr><td class="text-muted ps-0">Status</td><td><span class="badge bg-dark border border-secondary">{{ ucfirst($event->status) }}</span></td></tr>
                        <tr><td class="text-muted ps-0">Occurred</td><td>{{ $event->occurred_at?->format('M d, Y H:i') ?? '—' }}</td></tr>
                        @if($event->escalated_at)<tr><td class="text-muted ps-0">Escalated</td><td>{{ $event->escalated_at->format('M d, Y H:i') }}</td></tr>@endif
                        @if($event->resolved_at)<tr><td class="text-muted ps-0">Resolved</td><td>{{ $event->resolved_at->format('M d, Y H:i') }}</td></tr>@endif
                        @if($event->duration_days !== null)<tr><td class="text-muted ps-0">Duration</td><td>{{ $event->duration_days }} days</td></tr>@endif
                        <tr><td class="text-muted ps-0">Risk Score</td><td class="fw-bold text-warning">{{ $event->risk_score }}</td></tr>
                    </table>
                </div>

                {{-- AI Assessment --}}
                @if($event->ai_sentiment)
                <div class="card p-4 mb-4">
                    <h6 class="fw-semibold text-white mb-3"><i class="bi bi-robot me-2"></i>AI Assessment</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Sentiment</span>
                        <span class="badge {{ $event->ai_sentiment === 'bearish' ? 'bg-danger' : ($event->ai_sentiment === 'bullish' ? 'bg-success' : 'bg-secondary') }}">
                            {{ ucfirst($event->ai_sentiment) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Impact Level</span>
                        <span class="badge {{ $event->ai_impact_level === 'high' ? 'bg-danger' : ($event->ai_impact_level === 'medium' ? 'bg-warning' : 'bg-secondary') }}">
                            {{ ucfirst($event->ai_impact_level ?? 'unknown') }}
                        </span>
                    </div>
                    @if($event->ai_confidence_score)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Confidence</span>
                        <span class="fw-semibold">{{ $event->ai_confidence_score }}%</span>
                    </div>
                    @endif
                    @if($event->ai_affected_assets)
                    <div class="mt-3 pt-3 border-top border-secondary">
                        <div class="text-muted small mb-2">Affected Assets</div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($event->ai_affected_assets as $asset)
                                <span class="badge bg-dark border border-primary text-primary" style="font-size:0.7rem">{{ $asset }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Countries --}}
                @if($event->countries && count($event->countries) > 0)
                <div class="card p-4 mb-4">
                    <h6 class="fw-semibold text-white mb-3"><i class="bi bi-globe me-2"></i>Countries Involved</h6>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($event->countries as $country)
                            <span class="badge bg-dark border border-secondary">{{ $country }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Parent/Child Events --}}
                @if($event->parentEvent || $event->childEvents->count() > 0)
                <div class="card p-4 mb-4">
                    <h6 class="fw-semibold text-white mb-3"><i class="bi bi-diagram-3 me-2"></i>Related Events</h6>
                    @if($event->parentEvent)
                        <div class="mb-2">
                            <span class="text-muted small">Parent Event:</span>
                            <a href="{{ route('geopolitical.show', $event->parentEvent) }}" class="text-white small text-decoration-none">{{ Str::limit($event->parentEvent->title, 40) }}</a>
                        </div>
                    @endif
                    @foreach($event->childEvents as $child)
                        <div class="mb-1">
                            <span class="text-muted small">Escalation:</span>
                            <a href="{{ route('geopolitical.show', $child) }}" class="text-white small text-decoration-none">{{ Str::limit($child->title, 40) }}</a>
                        </div>
                    @endforeach
                </div>
                @endif

                {{-- Source Link --}}
                @if($event->source_url)
                <div class="card p-4 mb-4">
                    <h6 class="fw-semibold text-white mb-2"><i class="bi bi-link-45deg me-2"></i>Source</h6>
                    <a href="{{ $event->source_url }}" target="_blank" class="text-info small text-break">{{ Str::limit($event->source_url, 80) }}</a>
                </div>
                @endif
            </div>
        </div>

        {{-- Related Events --}}
        @if($relatedEvents->count() > 0)
        <h5 class="fw-semibold text-white mt-4 mb-3"><i class="bi bi-collection me-2"></i>Related Events</h5>
        <div class="row g-3">
            @foreach($relatedEvents as $related)
            <div class="col-md-6 col-xl-4">
                <div class="card p-3 h-100">
                    <a href="{{ route('geopolitical.show', $related) }}" class="text-white fw-semibold text-decoration-none" style="font-size:0.88rem">
                        {{ Str::limit($related->title, 50) }}
                    </a>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge bg-{{ $related->severity_badge }}" style="font-size:0.6rem">{{ ucfirst($related->severity) }}</span>
                        <span class="badge rounded-pill" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3); font-size:0.6rem">{{ $related->event_type_label }}</span>
                        <span class="text-muted" style="font-size:0.7rem">{{ $related->occurred_at?->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
