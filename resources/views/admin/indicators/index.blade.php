@extends('layouts.app')

@section('title', 'Economic Indicators - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="source-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Admin Module</div>
                <h1 class="h2 fw-bold text-white mb-1">Economic Event Processing</h1>
                <p class="text-muted mb-0">Track CPI, Core CPI, NFP, GDP, PPI, PMI, Interest Rates, Retail Sales & Unemployment Claims.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap align-self-start">
                <form method="POST" action="{{ route('admin.indicators.process-pending') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-gear me-1"></i> Process Pending
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.indicators.classify-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-tags me-1"></i> Classify All
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.indicators.recompute-surprises') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-calculator me-1"></i> Recompute Surprises
                    </button>
                </form>
                @if($indicators->isEmpty())
                <form method="POST" action="{{ route('admin.indicators.seed') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-cloud-download me-1"></i> Seed Indicators
                    </button>
                </form>
                @endif
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
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-white mb-0">{{ number_format($summary['total_events']) }}</div>
                    <div class="text-muted small text-uppercase">Total Events</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-success mb-0">{{ number_format($summary['beats']) }}</div>
                    <div class="text-muted small text-uppercase">Beats</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-danger mb-0">{{ number_format($summary['misses']) }}</div>
                    <div class="text-muted small text-uppercase">Misses</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-warning mb-0">{{ number_format($summary['pending_processing']) }}</div>
                    <div class="text-muted small text-uppercase">Pending</div>
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl">
                <div class="card p-3 text-center">
                    <div class="h4 fw-bold text-info mb-0">{{ number_format($summary['upcoming_high_impact']) }}</div>
                    <div class="text-muted small text-uppercase">Upcoming High</div>
                </div>
            </div>
        </div>

        {{-- 9 Tracked Indicators Grid --}}
        <h5 class="fw-semibold text-white mb-3"><i class="bi bi-bar-chart me-2"></i>Tracked Indicators</h5>
        <div class="row g-3 mb-4">
            @foreach(EconomicIndicator::INDICATORS as $code => $def)
                @php
                    $indicatorData = $summary['by_indicator'][$code] ?? null;
                    $latestEvent = $latestByIndicator[$code]['latest_event'] ?? null;
                @endphp
                <div class="col-md-6 col-xl-4">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge rounded-pill mb-1" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3); font-size: 0.7rem;">{{ strtoupper($code) }}</span>
                                <h6 class="fw-semibold text-white mb-0" style="font-size: 0.9rem;">{{ $def['name'] }}</h6>
                            </div>
                            <a href="{{ route('admin.indicators.show', $code) }}" class="btn btn-outline-light btn-sm px-2 py-0" style="font-size: 0.75rem;">View</a>
                        </div>
                        <div class="small text-muted mb-2">
                            <span class="badge bg-dark border border-secondary me-1">{{ ucfirst($def['category']) }}</span>
                            <span class="me-1">{{ $def['frequency'] }}</span>
                            &middot; {{ $def['unit'] ?: 'Index' }}
                        </div>
                        @if($latestEvent)
                            <div class="d-flex gap-3 small font-monospace mt-auto pt-2 border-top border-secondary">
                                <div>
                                    <span class="text-muted">A:</span>
                                    <span class="text-white fw-bold">{{ $latestEvent->actual ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-muted">F:</span>
                                    <span class="text-white-50">{{ $latestEvent->forecast ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-muted">P:</span>
                                    <span class="text-white-50">{{ $latestEvent->previous ?? '—' }}</span>
                                </div>
                                <div>
                                    @if($latestEvent->surprise_direction === 'beat')
                                        <span class="text-success"><i class="bi bi-arrow-up-short"></i></span>
                                    @elseif($latestEvent->surprise_direction === 'miss')
                                        <span class="text-danger"><i class="bi bi-arrow-down-short"></i></span>
                                    @else
                                        <span class="text-secondary">≈</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-muted small mt-auto pt-2 border-top border-secondary">No events recorded yet</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
