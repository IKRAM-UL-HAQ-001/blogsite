@extends('layouts.app')

@section('title', '{{ $indicatorDef["name"] }} - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Economic Indicator</div>
                <h1 class="h2 fw-bold text-white mb-1">{{ $indicatorDef['name'] }}</h1>
                <p class="text-muted mb-0">{{ $indicatorDef['description'] }}</p>
            </div>
            <a href="{{ route('admin.indicators.index') }}" class="btn btn-outline-light align-self-start">
                <i class="bi bi-arrow-left me-1"></i> Back to Indicators
            </a>
        </div>

        {{-- Indicator Metadata --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 text-center">
                    <div class="text-muted small text-uppercase">Total Events</div>
                    <div class="h4 fw-bold text-white">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center">
                    <div class="text-muted small text-uppercase">Beats</div>
                    <div class="h4 fw-bold text-success">{{ number_format($stats['beats']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center">
                    <div class="text-muted small text-uppercase">Misses</div>
                    <div class="h4 fw-bold text-danger">{{ number_format($stats['misses']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-center">
                    <div class="text-muted small text-uppercase">Avg Surprise</div>
                    <div class="h4 fw-bold text-info">{{ $stats['avg_surprise'] ? number_format($stats['avg_surprise'], 2) : '—' }}</div>
                </div>
            </div>
        </div>

        {{-- Events Table --}}
        <div class="card overflow-hidden">
            <div class="p-3 border-bottom border-secondary">
                <h6 class="fw-semibold text-white mb-0"><i class="bi bi-list-task me-2"></i>Event History</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Country</th>
                            <th>Importance</th>
                            <th class="text-center">Actual</th>
                            <th class="text-center">Forecast</th>
                            <th class="text-center">Previous</th>
                            <th class="text-center">Surprise</th>
                            <th>AI Analysis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr>
                                <td class="ps-4">
                                    <div class="text-white fw-semibold">{{ $event->release_time->format('M d, Y') }}</div>
                                    <div class="text-muted small">{{ $event->release_time->format('H:i') }} UTC</div>
                                </td>
                                <td><span class="badge bg-dark border border-secondary">{{ $event->country }}</span></td>
                                <td>
                                    @if($event->importance === 'high')
                                        <span class="badge text-bg-danger">High</span>
                                    @elseif($event->importance === 'medium')
                                        <span class="badge text-bg-warning">Medium</span>
                                    @else
                                        <span class="badge text-bg-secondary">Low</span>
                                    @endif
                                </td>
                                <td class="text-center font-monospace text-white fw-bold">{{ $event->actual ?? '—' }}</td>
                                <td class="text-center font-monospace text-white-50">{{ $event->forecast ?? '—' }}</td>
                                <td class="text-center font-monospace text-white-50">{{ $event->previous ?? '—' }}</td>
                                <td class="text-center font-monospace">
                                    @if($event->surprise_direction === 'beat')
                                        <span class="text-success fw-bold"><i class="bi bi-arrow-up-short"></i>+{{ number_format($event->surprise, 2) }}</span>
                                    @elseif($event->surprise_direction === 'miss')
                                        <span class="text-danger fw-bold"><i class="bi bi-arrow-down-short"></i>{{ number_format($event->surprise, 2) }}</span>
                                    @elseif($event->surprise_direction === 'inline')
                                        <span class="text-secondary">≈ 0</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($event->marketImpact)
                                        <span class="badge badge-{{ strtolower($event->marketImpact->sentiment) }}">
                                            {{ ucfirst($event->marketImpact->sentiment) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    No events found for this indicator.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $events->links() }}
        </div>
    </div>
</div>
@endsection
