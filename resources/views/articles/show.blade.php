@extends('layouts.app')

@section('title', $article->seo_title)
@section('meta_description', $article->seo_description)

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Feed</a></li>
            <li class="breadcrumb-item active text-muted" aria-current="page">Intelligence Report</li>
        </ol>
    </nav>

    <div class="row g-4">
        
        <!-- Main Article Content -->
        <div class="col-lg-8">
            <div class="card p-4 p-md-5 border-0" style="background-color: var(--bg-card);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="text-xs text-muted" style="font-size: 0.8rem;">
                        <i class="bi bi-calendar3 me-1"></i> {{ $article->published_at->format('M d, Y H:i') }} UTC
                    </span>
                    <span class="text-muted">•</span>
                    <span class="text-xs text-muted" style="font-size: 0.8rem;">
                        <i class="bi bi-eye me-1"></i> {{ $article->view_count }} views
                    </span>
                </div>
                
                <h1 class="h2 fw-bold text-white mb-4">{{ $article->title }}</h1>
                
                @if($article->featuredImage && !str_contains($article->featuredImage->file_path, 'dummy'))
                    <div class="mb-4 rounded-3 overflow-hidden">
                        <img src="{{ asset('storage/' . $article->featuredImage->file_path) }}" class="img-fluid w-100" alt="{{ $article->featuredImage->alt_text }}" style="max-height: 450px; object-fit: cover;">
                        <div class="bg-dark p-2 text-center text-muted" style="font-size: 0.75rem; border-top: 1px solid var(--bg-border);">
                            Featured Asset: Generated via DALL-E 3. Prompt: <em>"{{ $article->featuredImage->generation_prompt }}"</em>
                        </div>
                    </div>
                @endif
                
                <!-- Article Body (Parsed Markdown) -->
                <div class="article-body text-white-50" style="line-height: 1.8; font-size: 1.05rem;">
                    {!! $safeBodyHtml !!}
                </div>
                
                <!-- Focus Keywords -->
                @if($article->focus_keywords)
                    <div class="mt-4 pt-4 border-top border-secondary-subtle">
                        <span class="text-muted me-2" style="font-size: 0.9rem;">Keywords:</span>
                        @foreach(explode(',', $article->focus_keywords) as $keyword)
                            <span class="badge bg-dark border border-secondary-subtle text-muted me-1">{{ trim($keyword) }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Analytics -->
        <div class="col-lg-4">
            
            <!-- AI Sentiment Radar Card -->
            @if($article->marketImpact)
                <div class="card p-4 border-0 mb-4 sticky-lg-top" style="background-color: var(--bg-card); top: 90px; border: 1px solid var(--bg-border) !important;">
                    <h4 class="h6 fw-bold text-uppercase text-muted mb-4 d-flex align-items-center">
                        <i class="bi bi-cpu text-primary me-2"></i> AI Intelligence Insights
                    </h4>
                    
                    <div class="p-3 rounded-3 mb-4 text-center" style="background-color: rgba(255, 255, 255, 0.02); border: 1px solid var(--bg-border);">
                        <div class="text-xs text-muted mb-2" style="font-size: 0.8rem;">Market Sentiment Vector</div>
                        <div class="h2 fw-bold text-{{ strtolower($article->marketImpact->sentiment) }}">
                            {{ ucfirst($article->marketImpact->sentiment) }}
                        </div>
                        <div class="text-muted font-monospace" style="font-size: 0.9rem;">
                            Score: {{ $article->marketImpact->score > 0 ? '+' : '' }}{{ $article->marketImpact->score }} / 100
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between py-2 border-bottom border-secondary-subtle">
                            <span class="text-muted">Impact Priority</span>
                            <span class="badge text-bg-{{ $article->marketImpact->impact_level === 'high' ? 'danger' : ($article->marketImpact->impact_level === 'medium' ? 'warning' : 'secondary') }} text-uppercase">
                                {{ $article->marketImpact->impact_level }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom border-secondary-subtle">
                            <span class="text-muted">Affected Assets</span>
                            <div>
                                @if(is_array($article->marketImpact->affected_assets))
                                    @foreach($article->marketImpact->affected_assets as $asset)
                                        <span class="badge bg-dark border border-secondary-subtle text-white me-1">{{ $asset }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        @if($article->marketImpact->rawArticle && $article->marketImpact->rawArticle->newsSource)
                            <div class="d-flex justify-content-between py-2 border-bottom border-secondary-subtle">
                                <span class="text-muted">Original Source</span>
                                <span class="text-white text-truncate" style="max-width: 150px;">{{ $article->marketImpact->rawArticle->newsSource->name }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="p-3 rounded-3 bg-dark-subtle border border-secondary-subtle" style="background-color: rgba(0,0,0,0.25);">
                        <div class="fw-semibold text-white mb-2" style="font-size: 0.9rem;">Macro Implications Summary</div>
                        <p class="text-muted mb-0" style="font-size: 0.85rem; line-height: 1.6;">
                            {{ $article->marketImpact->market_summary }}
                        </p>
                    </div>
                </div>
            @endif
            
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .article-body h2 {
        color: var(--text-primary);
        font-weight: 600;
        margin-top: 2rem;
        margin-bottom: 1rem;
        font-size: 1.5rem;
        border-bottom: 1px solid var(--bg-border);
        padding-bottom: 0.5rem;
    }
    .article-body h3 {
        color: var(--text-primary);
        font-weight: 500;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        font-size: 1.25rem;
    }
    .article-body p {
        margin-bottom: 1.5rem;
    }
    .article-body blockquote {
        border-left: 4px solid var(--accent-primary);
        padding-left: 1.25rem;
        margin: 1.5rem 0;
        color: var(--text-secondary);
        font-style: italic;
    }
</style>
@endsection
