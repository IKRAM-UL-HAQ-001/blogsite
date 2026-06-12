@extends('layouts.app')

@section('title', $typeDef['name'] . ' Events - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <a href="{{ route('admin.geopolitical.dashboard') }}" class="text-muted small text-decoration-none mb-3 d-inline-block">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <span class="badge rounded-pill mb-2" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3); font-size: 0.7rem;">{{ strtoupper($code) }}</span>
                <h1 class="h3 fw-bold text-white mb-1">{{ $typeDef['name'] }} Events</h1>
                <p class="text-muted mb-0">{{ $typeDef['description'] }}</p>
            </div>
        </div>

        {{-- Type Info Card --}}
        <div class="card p-4 mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase mb-1">Category</div>
                    <div class="text-white">{{ ucfirst($typeDef['category']) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase mb-1">Default Severity</div>
                    <span class="badge bg-{{ $typeDef['default_severity'] === 'critical' ? 'danger' : ($typeDef['default_severity'] === 'high' ? 'warning' : 'info') }}">
                        {{ ucfirst($typeDef['default_severity']) }}
                    </span>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase mb-1">Keywords</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(array_slice($typeDef['keywords'], 0, 8) as $keyword)
                            <span class="badge bg-dark border border-secondary" style="font-size:0.65rem">{{ $keyword }}</span>
                        @endforeach
                        @if(count($typeDef['keywords']) > 8)
                            <span class="badge bg-dark text-muted" style="font-size:0.65rem">+{{ count($typeDef['keywords']) - 8 }} more</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Events Table --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="border-secondary">
                            <th style="min-width:250px">Event</th>
                            <th>Severity</th>
                            <th>Region</th>
                            <th>Escalation</th>
                            <th>AI Impact</th>
                            <th>Occurred</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td>
                                    <a href="{{ route('geopolitical.show', $event) }}" class="text-white fw-semibold text-decoration-none" style="font-size:0.88rem">
                                        {{ Str::limit($event->title, 55) }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $event->severity_badge }}" style="font-size:0.7rem">{{ ucfirst($event->severity) }}</span>
                                </td>
                                <td class="small text-muted">{{ $event->region_label }}</td>
                                <td>
                                    @php $escColors = ['New' => 'secondary', 'Monitoring' => 'info', 'Escalating' => 'warning', 'Critical' => 'danger']; @endphp
                                    <span class="badge bg-{{ $escColors[$event->escalation_label] ?? 'secondary' }}" style="font-size:0.65rem">{{ $event->escalation_label }}</span>
                                </td>
                                <td>
                                    @if($event->ai_impact_level)
                                        <span class="badge {{ $event->ai_impact_level === 'high' ? 'bg-danger' : ($event->ai_impact_level === 'medium' ? 'bg-warning' : 'bg-secondary') }}" style="font-size:0.65rem">{{ ucfirst($event->ai_impact_level) }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $event->occurred_at?->diffForHumans() ?? '—' }}</td>
                                <td><span class="badge bg-dark border border-secondary" style="font-size:0.65rem">{{ ucfirst($event->status) }}</span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form method="POST" action="{{ route('admin.geopolitical.process-event', $event) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-primary btn-sm px-2 py-0" style="font-size:0.65rem">Process</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.geopolitical.resolve-event', $event) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success btn-sm px-2 py-0" style="font-size:0.65rem">Resolve</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No {{ $typeDef['name'] }} events found.</td>
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
