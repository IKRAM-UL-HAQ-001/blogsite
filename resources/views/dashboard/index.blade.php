@extends('layouts.app')

@section('title', 'Enterprise Finance Admin Dashboard - FinIntel.AI')

@section('content')
<div class="container-fluid px-3 px-lg-4">
    <div class="admin-shell">
        <div class="d-flex flex-column flex-xl-row justify-content-between gap-3 mb-4">
            <div>
                <div class="text-uppercase text-primary fw-semibold small mb-2">Enterprise Finance Command Center</div>
                <h1 class="h2 fw-bold text-white mb-1">Admin Dashboard</h1>
                <p class="text-muted mb-0">Monitor publishing velocity, economic intelligence, ingestion throughput, AI production, and traffic health.</p>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-start">
                <a href="{{ route('admin.news-sources.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-broadcast-pin me-1"></i> Manage Sources
                </a>

                @if($stats['sources'] === 0)
                    <form action="{{ route('admin.seed-sources') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Seed Sources
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.trigger-ingest') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> Sync Ingest
                    </button>
                </form>

                <form action="{{ route('admin.trigger-analysis') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-cpu-fill me-1"></i> Run AI Analysis
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show bg-success-subtle text-success border border-success-subtle mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="metric-card h-100">
                    <div class="metric-icon text-primary bg-primary-subtle"><i class="bi bi-journals"></i></div>
                    <div class="metric-label">Total Articles</div>
                    <div class="metric-value">{{ number_format($stats['total_articles']) }}</div>
                    <div class="metric-foot">All editorial records in the system</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="metric-card h-100">
                    <div class="metric-icon text-success bg-success-subtle"><i class="bi bi-check2-circle"></i></div>
                    <div class="metric-label">Published Articles</div>
                    <div class="metric-value">{{ number_format($stats['published_articles']) }}</div>
                    <div class="metric-foot">Live market intelligence pages</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="metric-card h-100">
                    <div class="metric-icon text-warning bg-warning-subtle"><i class="bi bi-hourglass-split"></i></div>
                    <div class="metric-label">Pending Articles</div>
                    <div class="metric-value">{{ number_format($stats['pending_articles']) }}</div>
                    <div class="metric-foot">Draft and scheduled pipeline</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="metric-card h-100">
                    <div class="metric-icon text-info bg-info-subtle"><i class="bi bi-calendar2-event"></i></div>
                    <div class="metric-label">Economic Events Today</div>
                    <div class="metric-value">{{ number_format($stats['economic_events_today']) }}</div>
                    <div class="metric-foot">Calendar releases dated today</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="metric-card h-100">
                    <div class="metric-icon text-info bg-info-subtle"><i class="bi bi-rss"></i></div>
                    <div class="metric-label">News Processed Today</div>
                    <div class="metric-value">{{ number_format($stats['news_processed_today']) }}</div>
                    <div class="metric-foot">Ingested items cleared from pending</div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="metric-card h-100">
                    <div class="metric-icon text-danger bg-danger-subtle"><i class="bi bi-stars"></i></div>
                    <div class="metric-label">AI Generated Articles</div>
                    <div class="metric-value">{{ number_format($stats['ai_generated_articles']) }}</div>
                    <div class="metric-foot">Articles produced from impact analysis</div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="metric-card traffic-summary h-100">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label">Traffic Overview</div>
                            <div class="metric-value">{{ number_format($stats['traffic_total']) }}</div>
                            <div class="metric-foot">{{ number_format($stats['unique_visitors']) }} unique visitors in tracked analytics</div>
                        </div>
                        <div class="text-end">
                            <div class="metric-icon text-primary bg-primary-subtle ms-auto"><i class="bi bi-graph-up-arrow"></i></div>
                            <div class="small text-muted mt-2">Today</div>
                            <div class="fw-bold text-white">{{ number_format($stats['traffic_today']) }}</div>
                        </div>
                    </div>

                    <div class="traffic-bars mt-4">
                        @foreach($trafficOverview['labels'] as $index => $label)
                            @php
                                $views = $trafficOverview['views'][$index];
                                $height = max(10, round(($views / $trafficOverview['max']) * 100));
                            @endphp
                            <div class="traffic-bar-item">
                                <div class="traffic-bar-track">
                                    <div class="traffic-bar-fill" data-height="{{ $height }}" title="{{ $label }}: {{ number_format($views) }} views"></div>
                                </div>
                                <div class="traffic-bar-label">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-7">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <div>
                            <h2 class="h5 mb-1">Publishing Pipeline</h2>
                            <p class="text-muted mb-0 small">Article status mix across the editorial system.</p>
                        </div>
                    </div>

                    @php
                        $published = $stats['published_articles'];
                        $pending = $stats['pending_articles'];
                        $total = max($stats['total_articles'], 1);
                        $publishedWidth = round(($published / $total) * 100);
                        $pendingWidth = round(($pending / $total) * 100);
                    @endphp

                    <div class="pipeline-meter mb-4">
                        <div class="pipeline-published" data-width="{{ $publishedWidth }}"></div>
                        <div class="pipeline-pending" data-width="{{ $pendingWidth }}"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="pipeline-tile">
                                <div class="small text-muted">Published Share</div>
                                <div class="h4 mb-0 text-white">{{ $publishedWidth }}%</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="pipeline-tile">
                                <div class="small text-muted">Pending Share</div>
                                <div class="h4 mb-0 text-white">{{ $pendingWidth }}%</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="pipeline-tile">
                                <div class="small text-muted">AI Coverage</div>
                                <div class="h4 mb-0 text-white">{{ round(($stats['ai_generated_articles'] / $total) * 100) }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <div>
                            <h2 class="h5 mb-1">Operational Snapshot</h2>
                            <p class="text-muted mb-0 small">Source, event, and AI activity totals.</p>
                        </div>
                    </div>

                    <div class="snapshot-list">
                        <div class="snapshot-row">
                            <span><i class="bi bi-broadcast-pin text-primary me-2"></i> News Sources</span>
                            <strong>{{ number_format($stats['sources']) }}</strong>
                        </div>
                        <div class="snapshot-row">
                            <span><i class="bi bi-newspaper text-info me-2"></i> Raw News Items</span>
                            <strong>{{ number_format($stats['raw_articles']) }}</strong>
                        </div>
                        <div class="snapshot-row">
                            <span><i class="bi bi-calendar-week text-warning me-2"></i> Economic Events</span>
                            <strong>{{ number_format($stats['events']) }}</strong>
                        </div>
                        <div class="snapshot-row">
                            <span><i class="bi bi-cpu text-success me-2"></i> AI Impact Analyses</span>
                            <strong>{{ number_format($stats['impacts']) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-4">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <div>
                            <h2 class="h5 mb-1">Click-Through Rate</h2>
                            <p class="text-muted mb-0 small">Views vs impressions over time.</p>
                        </div>
                    </div>
                    <div class="metric-card h-100 border-0 bg-transparent">
                        <div class="metric-icon text-primary bg-primary-subtle"><i class="bi bi-cursor-fill"></i></div>
                        <div class="metric-label">Overall CTR</div>
                        <div class="metric-value">{{ number_format($ctrOverall, 2) }}%</div>
                        <div class="metric-foot">Calculated from article page views and listing impressions.</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <div>
                            <h2 class="h5 mb-1">Top Articles</h2>
                            <p class="text-muted mb-0 small">Most viewed intelligence reports.</p>
                        </div>
                    </div>
                    <div class="activity-list">
                        @forelse($topArticles as $article)
                            <div class="activity-item">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <div class="fw-semibold text-white text-truncate">{{ $article->title }}</div>
                                    <span class="text-muted small">{{ number_format($article->view_count) }}</span>
                                </div>
                                <div class="small text-muted">{{ $article->published_at?->format('M d') ?? 'No date' }}</div>
                            </div>
                        @empty
                            <div class="empty-state">No published articles to rank yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <div>
                            <h2 class="h5 mb-1">Popular Categories</h2>
                            <p class="text-muted mb-0 small">Categories generating the most audience interest.</p>
                        </div>
                    </div>
                    <div class="activity-list">
                        @forelse($popularCategories as $category)
                            <div class="activity-item">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <div class="fw-semibold text-white text-truncate">{{ $category->name }}</div>
                                    <span class="text-muted small">{{ number_format($category->total_views) }}</span>
                                </div>
                                <div class="small text-muted">{{ number_format($category->article_count) }} published articles</div>
                            </div>
                        @empty
                            <div class="empty-state">No category traffic yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-12">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <div>
                            <h2 class="h5 mb-1">Traffic Sources</h2>
                            <p class="text-muted mb-0 small">Where impressions and clicks are coming from.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>Medium</th>
                                    <th>Views</th>
                                    <th>Clicks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($trafficSources as $source)
                                    <tr>
                                        <td>{{ $source->source }}</td>
                                        <td>{{ $source->medium }}</td>
                                        <td>{{ number_format($source->views) }}</td>
                                        <td>{{ number_format($source->clicks) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No traffic source data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <h2 class="h5 mb-0">Recent News</h2>
                    </div>

                    <div class="activity-list">
                        @forelse($recentNews as $news)
                            <div class="activity-item">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <div class="fw-semibold text-white text-truncate">{{ $news->title }}</div>
                                    <span class="status-pill status-{{ $news->status }}">{{ ucfirst($news->status) }}</span>
                                </div>
                                <div class="small text-muted">
                                    {{ $news->newsSource?->name ?? 'Unknown source' }} · {{ optional($news->published_at)->diffForHumans() ?? 'No publish date' }}
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">No news feeds ingested yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <h2 class="h5 mb-0">Recent Articles</h2>
                    </div>

                    <div class="activity-list">
                        @forelse($recentArticles as $article)
                            <div class="activity-item">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <div class="fw-semibold text-white text-truncate">{{ $article->title }}</div>
                                    <span class="status-pill status-{{ $article->status }}">{{ ucfirst($article->status) }}</span>
                                </div>
                                <div class="small text-muted">
                                    {{ number_format($article->view_count) }} views · {{ $article->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">No articles generated yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="dashboard-panel h-100">
                    <div class="panel-header">
                        <h2 class="h5 mb-0">AI Activity</h2>
                    </div>

                    <div class="activity-list">
                        @forelse($recentImpacts as $impact)
                            <div class="activity-item">
                                <div class="d-flex justify-content-between gap-3 mb-1">
                                    <div class="fw-semibold text-white text-truncate">
                                        {{ $impact->rawArticle?->title ?? $impact->economicEvent?->event_name ?? 'Market impact analysis' }}
                                    </div>
                                    <span class="badge badge-{{ strtolower($impact->sentiment) }}">{{ ucfirst($impact->sentiment) }}</span>
                                </div>
                                <div class="small text-muted">
                                    Score {{ $impact->score }} · {{ strtoupper($impact->impact_level) }} impact
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">No AI analyses generated yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .admin-shell {
        max-width: 1640px;
        margin: 0 auto;
    }

    .metric-card,
    .dashboard-panel {
        background: #111622;
        border: 1px solid #1d2436;
        border-radius: 8px;
        color: #f8fafc;
    }

    .metric-card {
        padding: 1.15rem;
        min-height: 164px;
    }

    .metric-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }

    .metric-label {
        color: #94a3b8;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .metric-value {
        color: #f8fafc;
        font-size: 2rem;
        font-weight: 800;
        line-height: 1.1;
        margin-top: 0.35rem;
    }

    .metric-foot {
        color: #94a3b8;
        font-size: 0.86rem;
        margin-top: 0.6rem;
    }

    .traffic-summary {
        min-height: 164px;
    }

    .traffic-bars {
        height: 84px;
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 0.7rem;
        align-items: end;
    }

    .traffic-bar-item {
        min-width: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 0.4rem;
    }

    .traffic-bar-track {
        height: 58px;
        background: #0a0d14;
        border: 1px solid #1d2436;
        border-radius: 6px;
        display: flex;
        align-items: end;
        overflow: hidden;
    }

    .traffic-bar-fill {
        width: 100%;
        min-height: 6px;
        background: linear-gradient(180deg, #38bdf8 0%, #2563eb 100%);
    }

    .traffic-bar-label {
        color: #94a3b8;
        font-size: 0.68rem;
        text-align: center;
        white-space: nowrap;
    }

    .dashboard-panel {
        padding: 1.25rem;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .pipeline-meter {
        display: flex;
        height: 18px;
        overflow: hidden;
        background: #0a0d14;
        border: 1px solid #1d2436;
        border-radius: 6px;
    }

    .pipeline-published {
        background: #10b981;
    }

    .pipeline-pending {
        background: #f59e0b;
    }

    .pipeline-tile {
        background: #0a0d14;
        border: 1px solid #1d2436;
        border-radius: 8px;
        padding: 1rem;
    }

    .snapshot-list,
    .activity-list {
        display: grid;
        gap: 0.75rem;
    }

    .snapshot-row,
    .activity-item {
        background: #0a0d14;
        border: 1px solid #1d2436;
        border-radius: 8px;
        padding: 0.85rem 1rem;
    }

    .snapshot-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .status-pill {
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        line-height: 1;
        padding: 0.32rem 0.5rem;
        white-space: nowrap;
    }

    .status-published,
    .status-analyzed {
        background: rgba(16, 185, 129, 0.13);
        color: #10b981;
    }

    .status-draft,
    .status-pending,
    .status-scheduled {
        background: rgba(245, 158, 11, 0.14);
        color: #f59e0b;
    }

    .status-failed {
        background: rgba(239, 68, 68, 0.14);
        color: #ef4444;
    }

    .empty-state {
        color: #94a3b8;
        background: #0a0d14;
        border: 1px dashed #1d2436;
        border-radius: 8px;
        padding: 1.25rem;
        text-align: center;
    }

    @media (max-width: 575.98px) {
        .metric-value {
            font-size: 1.65rem;
        }

        .traffic-bars {
            gap: 0.35rem;
        }

        .traffic-bar-label {
            font-size: 0.58rem;
        }
    }
</style>
@endsection
