@extends('layouts.app')

@section('title', 'Economic Calendar - FinIntel.AI')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 fw-bold text-white mb-1">Economic Calendar</h1>
            <p class="text-muted mb-0">Track CPI, Core CPI, NFP, GDP, PPI, PMI, Interest Rates, Retail Sales & Unemployment Claims.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card p-3 mb-4">
        <form method="GET" action="{{ route('calendar.index') }}" class="row g-3">
            <div class="col-md-3">
                <select name="indicator" class="form-select bg-transparent border-secondary-subtle text-white-50" onchange="this.form.submit()">
                    <option value="" class="bg-dark">All Indicators</option>
                    @foreach($indicators as $code => $def)
                        <option value="{{ $code }}" {{ request('indicator') == $code ? 'selected' : '' }} class="bg-dark">{{ $def['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="importance" class="form-select bg-transparent border-secondary-subtle text-white-50" onchange="this.form.submit()">
                    <option value="" class="bg-dark">All Impact</option>
                    <option value="high" {{ request('importance') == 'high' ? 'selected' : '' }} class="bg-dark">🔴 High</option>
                    <option value="medium" {{ request('importance') == 'medium' ? 'selected' : '' }} class="bg-dark">🟡 Medium</option>
                    <option value="low" {{ request('importance') == 'low' ? 'selected' : '' }} class="bg-dark">🟢 Low</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="surprise" class="form-select bg-transparent border-secondary-subtle text-white-50" onchange="this.form.submit()">
                    <option value="" class="bg-dark">All Results</option>
                    <option value="beat" {{ request('surprise') == 'beat' ? 'selected' : '' }} class="bg-dark">📈 Beat</option>
                    <option value="miss" {{ request('surprise') == 'miss' ? 'selected' : '' }} class="bg-dark">📉 Miss</option>
                    <option value="inline" {{ request('surprise') == 'inline' ? 'selected' : '' }} class="bg-dark">➡️ Inline</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="period" class="form-select bg-transparent border-secondary-subtle text-white-50" onchange="this.form.submit()">
                    <option value="" class="bg-dark">All Time</option>
                    <option value="today" {{ request('period') == 'today' ? 'selected' : '' }} class="bg-dark">Today</option>
                    <option value="week" {{ request('period') == 'week' ? 'selected' : '' }} class="bg-dark">This Week</option>
                    <option value="upcoming" {{ request('period') == 'upcoming' ? 'selected' : '' }} class="bg-dark">Upcoming</option>
                </select>
            </div>
            <div class="col-md-1">
                <input type="text" name="country" class="form-control bg-transparent border-secondary-subtle text-white" placeholder="USD" value="{{ request('country') }}" maxlength="3">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill flex-fill">Apply</button>
                <a href="{{ route('calendar.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Reset</a>
            </div>
        </form>
    </div>

    <!-- Event List Table -->
    <div class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr class="border-bottom" style="border-color: var(--bg-border) !important;">
                        <th class="ps-4 py-3 text-muted text-uppercase" style="font-size: 0.75rem;">Time (UTC)</th>
                        <th class="py-3 text-muted text-uppercase" style="font-size: 0.75rem;">Currency</th>
                        <th class="py-3 text-muted text-uppercase" style="font-size: 0.75rem;">Indicator</th>
                        <th class="py-3 text-muted text-uppercase" style="font-size: 0.75rem;">Event</th>
                        <th class="py-3 text-muted text-uppercase" style="font-size: 0.75rem;">Impact</th>
                        <th class="py-3 text-muted text-uppercase text-center" style="font-size: 0.75rem;">Actual</th>
                        <th class="py-3 text-muted text-uppercase text-center" style="font-size: 0.75rem;">Forecast</th>
                        <th class="py-3 text-muted text-uppercase text-center" style="font-size: 0.75rem;">Previous</th>
                        <th class="py-3 text-muted text-uppercase text-center" style="font-size: 0.75rem;">Surprise</th>
                        <th class="py-3 text-muted text-uppercase text-end pe-4" style="font-size: 0.75rem;">AI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr class="border-bottom" style="border-color: var(--bg-border) !important;">
                            <td class="ps-4 text-white-50" style="font-size: 0.9rem;">
                                {{ $event->release_time->format('H:i') }}
                                <div class="text-xs text-muted" style="font-size: 0.75rem;">{{ $event->release_time->format('M d') }}</div>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary-subtle px-2 py-1 text-white fw-bold">
                                    {{ $event->country }}
                                </span>
                            </td>
                            <td>
                                @if($event->indicator_type && isset($indicators[$event->indicator_type]))
                                    <span class="badge rounded-pill" style="background: rgba(37,99,235,0.15); color: #93c5fd; border: 1px solid rgba(37,99,235,0.3); font-size: 0.7rem;">
                                        {{ strtoupper($event->indicator_type) }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.7rem;">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-white fw-semibold" style="font-size: 0.95rem;">{{ $event->event_name }}</div>
                            </td>
                            <td>
                                @if($event->importance === 'high')
                                    <span class="badge text-bg-danger text-uppercase" style="font-size: 0.7rem;">High</span>
                                @elseif($event->importance === 'medium')
                                    <span class="badge text-bg-warning text-uppercase" style="font-size: 0.7rem;">Medium</span>
                                @else
                                    <span class="badge text-bg-secondary text-uppercase" style="font-size: 0.7rem;">Low</span>
                                @endif
                            </td>
                            <td class="text-center font-monospace" style="font-size: 0.9rem;">
                                @if($event->actual)
                                    <span class="text-white fw-bold">{{ $event->actual }}</span>
                                @else
                                    <span class="text-muted">Pending</span>
                                @endif
                            </td>
                            <td class="text-center font-monospace text-white-50" style="font-size: 0.9rem;">
                                {{ $event->forecast ?? '—' }}
                            </td>
                            <td class="text-center font-monospace text-white-50" style="font-size: 0.9rem;">
                                {{ $event->previous ?? '—' }}
                            </td>
                            <td class="text-center font-monospace" style="font-size: 0.85rem;">
                                @if($event->surprise_direction === 'beat')
                                    <span class="text-success fw-bold">
                                        <i class="bi bi-arrow-up-short"></i>{{ $event->surprise > 0 ? '+' : '' }}{{ number_format($event->surprise, 2) }}
                                    </span>
                                @elseif($event->surprise_direction === 'miss')
                                    <span class="text-danger fw-bold">
                                        <i class="bi bi-arrow-down-short"></i>{{ number_format($event->surprise, 2) }}
                                    </span>
                                @elseif($event->surprise_direction === 'inline')
                                    <span class="text-secondary">≈ 0</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($event->marketImpact)
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <span class="badge badge-{{ strtolower($event->marketImpact->sentiment) }}">
                                            {{ ucfirst($event->marketImpact->sentiment) }}
                                        </span>
                                        @if($event->marketImpact->article)
                                            <a href="{{ route('articles.show', $event->marketImpact->article->slug) }}" class="btn btn-outline-primary btn-xs py-0 px-2 rounded-pill" style="font-size: 0.75rem;">Report</a>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted" style="font-size: 0.8rem;">
                                        @if($event->status === 'pending')
                                            <i class="bi bi-cpu text-primary me-1"></i> Queued
                                        @else
                                            <i class="bi bi-dash-circle text-muted"></i> —
                                        @endif
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-3"></i>
                                No economic events found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $events->links('pagination::bootstrap-5') }}
    </div>

</div>
@endsection
