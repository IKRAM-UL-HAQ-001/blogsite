@extends('layouts.app')

@section('title', 'Geopolitical Risk Monitor - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <div class="mb-4">
            <div class="text-uppercase text-primary fw-semibold small mb-2">Risk Intelligence</div>
            <h1 class="h2 fw-bold text-white mb-1">Geopolitical Risk Monitor</h1>
            <p class="text-muted mb-0">Track Wars, Military Escalations, Trade Wars, Sanctions, Energy Crises, Political Elections & Banking Crises.</p>
        </div>

        {{-- Filters --}}
        <div class="card p-3 mb-4">
            <form method="GET" action="{{ route('geopolitical.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Event Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($eventTypes as $code => $type)
                            <option value="{{ $code }}" {{ $filters['type'] ?? '' === $code ? 'selected' : '' }}>{{ $type['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Severity</label>
                    <select name="severity" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="high" {{ ($filters['severity'] ?? '') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ ($filters['severity'] ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ ($filters['severity'] ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Region</label>
                    <select name="region" class="form-select form-select-sm">
                        <option value="">All Regions</option>
                        @foreach($regions as $code => $name)
                            <option value="{{ $code }}" {{ ($filters['region'] ?? '') === $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($categories as $code => $name)
                            <option value="{{ $code }}" {{ ($filters['category'] ?? '') === $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('geopolitical.index') }}" class="btn btn-outline-secondary btn-sm w-100">Clear</a>
                </div>
            </form>
        </div>

        {{-- Events Table --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="border-secondary">
                            <th style="min-width:250px">Event</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Region</th>
                            <th>Escalation</th>
                            <th>Impact</th>
                            <th>Occurred</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td>
                                    <a href="{{ route('geopolitical.show', $event) }}" class="text-white fw-semibold text-decoration-none" style="font-size:0.88rem">
                                        {{ Str::limit($event->title, 60) }}
                                    </a>
                                    @if($event->primary_country)
                                        <span class="badge bg-dark border border-secondary ms-1" style="font-size:0.65rem">{{ $event->primary_country }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($event->event_type)
                                        <span class="badge rounded-pill" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3); font-size:0.7rem">
                                            {{ $event->event_type_label }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $event->severity_badge }} {{ $event->severity === 'critical' ? 'text-white' : '' }}" style="font-size:0.7rem">
                                        {{ ucfirst($event->severity) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $event->region_label }}</td>
                                <td>
                                    @php
                                        $escColors = ['New' => 'secondary', 'Monitoring' => 'info', 'Escalating' => 'warning', 'Critical' => 'danger'];
                                        $escColor = $escColors[$event->escalation_label] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $escColor }}" style="font-size:0.65rem">{{ $event->escalation_label }}</span>
                                </td>
                                <td>
                                    @if($event->ai_impact_level)
                                        <span class="badge {{ $event->ai_impact_level === 'high' ? 'bg-danger' : ($event->ai_impact_level === 'medium' ? 'bg-warning' : 'bg-secondary') }}" style="font-size:0.65rem">
                                            {{ ucfirst($event->ai_impact_level) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $event->occurred_at?->diffForHumans() ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('geopolitical.show', $event) }}" class="btn btn-outline-light btn-sm px-2 py-0" style="font-size:0.7rem">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No geopolitical events found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $events->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
