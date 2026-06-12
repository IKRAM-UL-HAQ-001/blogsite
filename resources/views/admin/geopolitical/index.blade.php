@extends('layouts.app')

@section('title', 'Geopolitical Risk Dashboard - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Admin Module</div>
                <h1 class="h2 fw-bold text-white mb-1">Geopolitical Risk Analysis</h1>
                <p class="text-muted mb-0">Monitor Wars, Military Escalations, Trade Wars, Sanctions, Energy Crises, Political Elections & Banking Crises.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-self-start">
                <form method="POST" action="{{ route('admin.geopolitical.process-pending') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-gear me-1"></i> Process Pending</button>
                </form>
                <form method="POST" action="{{ route('admin.geopolitical.classify-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-tags me-1"></i> Classify All</button>
                </form>
                <form method="POST" action="{{ route('admin.geopolitical.detect-regions') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-info btn-sm"><i class="bi bi-geo me-1"></i> Detect Regions</button>
                </form>
                <form method="POST" action="{{ route('admin.geopolitical.detect-escalations') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bi bi-exclamation-triangle me-1"></i> Detect Escalations</button>
                </form>
                <form method="POST" action="{{ route('admin.geopolitical.seed-types') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm"><i class="bi bi-cloud-download me-1"></i> Seed Types</button>
                </form>
            </div>
        </div>

        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Summary Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-white mb-0">{{ number_format($summary['total']) }}</div>
                    <div class="text-muted small text-uppercase">Total Events</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-warning mb-0">{{ number_format($summary['active']) }}</div>
                    <div class="text-muted small text-uppercase">Active</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-danger mb-0">{{ number_format($summary['critical']) }}</div>
                    <div class="text-muted small text-uppercase">Critical</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-info mb-0">{{ number_format($summary['escalating']) }}</div>
                    <div class="text-muted small text-uppercase">Escalating</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-secondary mb-0">{{ number_format($summary['pending']) }}</div>
                    <div class="text-muted small text-uppercase">Pending</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-success mb-0">{{ number_format($summary['analyzed']) }}</div>
                    <div class="text-muted small text-uppercase">Analyzed</div>
                </div>
            </div>
        </div>

        {{-- Severity Breakdown --}}
        <h5 class="fw-semibold text-white mb-3"><i class="bi bi-shield-exclamation me-2"></i>Severity Distribution</h5>
        <div class="row g-3 mb-4">
            @foreach(['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'secondary'] as $level => $color)
                <div class="col-6 col-md-3">
                    <div class="card p-3 text-center border-{{ $color }}">
                        <div class="h5 fw-bold text-{{ $color }} mb-0">{{ $summary['by_severity'][$level] ?? 0 }}</div>
                        <div class="text-muted small text-uppercase">{{ ucfirst($level) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 7 Event Types Grid --}}
        <h5 class="fw-semibold text-white mb-3"><i class="bi bi-bar-chart me-2"></i>Tracked Event Types</h5>
        <div class="row g-3 mb-4">
            @foreach($eventTypes as $code => $type)
                @php
                    $typeData = $summary['by_type'][$code] ?? ['count' => 0, 'active' => 0, 'critical' => 0];
                    $latestEvent = $latestByType[$code]['event'] ?? null;
                @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge rounded-pill mb-1" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3); font-size: 0.7rem;">{{ strtoupper($code) }}</span>
                                <h6 class="fw-semibold text-white mb-0" style="font-size: 0.9rem;">{{ $type['name'] }}</h6>
                            </div>
                            <a href="{{ route('admin.geopolitical.type', $code) }}" class="btn btn-outline-light btn-sm px-2 py-0" style="font-size: 0.75rem;">View</a>
                        </div>
                        <div class="small text-muted mb-2">
                            <span class="badge bg-dark border border-secondary me-1">{{ ucfirst($type['category']) }}</span>
                            <span class="text-{{ $type['default_severity'] === 'critical' ? 'danger' : ($type['default_severity'] === 'high' ? 'warning' : 'info') }}">{{ ucfirst($type['default_severity']) }} default</span>
                        </div>
                        <div class="d-flex gap-3 small font-monospace mt-auto pt-2 border-top border-secondary">
                            <div><span class="text-muted">Total:</span> <span class="text-white fw-bold">{{ $typeData['count'] }}</span></div>
                            <div><span class="text-muted">Active:</span> <span class="text-warning fw-bold">{{ $typeData['active'] }}</span></div>
                            <div><span class="text-muted">Crit:</span> <span class="text-danger fw-bold">{{ $typeData['critical'] }}</span></div>
                        </div>
                        @if($latestEvent)
                            <div class="mt-2 pt-2 border-top border-secondary">
                                <a href="{{ route('geopolitical.show', $latestEvent) }}" class="text-white small text-decoration-none" style="font-size:0.78rem">
                                    <i class="bi bi-clock me-1"></i>{{ $latestEvent->occurred_at?->diffForHumans() }}: {{ Str::limit($latestEvent->title, 40) }}
                                </a>
                            </div>
                        @else
                            <div class="text-muted small mt-2 pt-2 border-top border-secondary">No events recorded yet</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Top Risk Events --}}
        @if($summary['top_risk_events']->count() > 0)
        <h5 class="fw-semibold text-white mb-3"><i class="bi bi-exclamation-triangle me-2"></i>Top Risk Events</h5>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="border-secondary">
                            <th>Event</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Region</th>
                            <th>Escalation</th>
                            <th>Risk Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary['top_risk_events'] as $event)
                            <tr>
                                <td><a href="{{ route('geopolitical.show', $event) }}" class="text-white text-decoration-none">{{ Str::limit($event->title, 45) }}</a></td>
                                <td><span class="badge rounded-pill" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3); font-size:0.7rem">{{ $event->event_type_label }}</span></td>
                                <td><span class="badge bg-{{ $event->severity_badge }}" style="font-size:0.7rem">{{ ucfirst($event->severity) }}</span></td>
                                <td class="small text-muted">{{ $event->region_label }}</td>
                                <td><span class="badge bg-{{ $event->escalation_level >= 2 ? 'warning' : 'secondary' }}" style="font-size:0.65rem">{{ $event->escalation_label }}</span></td>
                                <td class="fw-bold text-warning">{{ $event->risk_score }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form method="POST" action="{{ route('admin.geopolitical.process-event', $event) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-primary btn-sm px-2 py-0" style="font-size:0.65rem">Process</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.geopolitical.escalate-event', $event) }}">
                                            @csrf
                                            <input type="hidden" name="level" value="{{ min($event->escalation_level + 1, 3) }}">
                                            <button type="submit" class="btn btn-outline-warning btn-sm px-2 py-0" style="font-size:0.65rem">Escalate</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.geopolitical.resolve-event', $event) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm px-2 py-0" style="font-size:0.65rem">Resolve</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Regions --}}
        @if(count($summary['by_region']) > 0)
        <h5 class="fw-semibold text-white mt-4 mb-3"><i class="bi bi-globe me-2"></i>Regional Distribution</h5>
        <div class="row g-3">
            @foreach($summary['by_region'] as $code => $regionData)
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card p-3 text-center">
                        <div class="h5 fw-bold text-white mb-0">{{ $regionData['count'] }}</div>
                        <div class="text-muted small">{{ $regionData['name'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
