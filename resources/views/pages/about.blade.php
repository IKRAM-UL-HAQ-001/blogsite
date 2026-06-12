@extends('layouts.app')

@section('title', 'About FinIntel.AI - AI Financial Intelligence Platform')
@section('meta_description', 'Learn how FinIntel.AI combines autonomous news ingestion, market impact scoring, and AI content generation to power financial intelligence.' )

@section('content')
<div class="container py-4">
    <div class="row align-items-center mb-5">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold">About FinIntel.AI</h1>
            <p class="lead text-muted">A next-generation finance intelligence platform that unifies AI news ingestion, market impact scoring, and automated content generation for institutional and quant research workflows.</p>
        </div>
        <div class="col-lg-5">
            <div class="card p-4">
                <h5 class="mb-3">Platform Highlights</h5>
                <ul class="list-unstyled text-muted lh-lg">
                    <li><i class="bi bi-check-circle-fill text-primary me-2"></i> Autonomous news & data ingestion</li>
                    <li><i class="bi bi-check-circle-fill text-primary me-2"></i> AI market impact scoring and sentiment tagging</li>
                    <li><i class="bi bi-check-circle-fill text-primary me-2"></i> SEO-optimized intelligence report generation</li>
                    <li><i class="bi bi-check-circle-fill text-primary me-2"></i> Economic calendar and geopolitical monitoring</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h5>Automated Pipeline</h5>
                <p class="text-muted">Scheduled ingestion, scoring, and publishing workflows keep the intelligence feed fresh and actionable without manual overhead.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h5>Insightful Analysis</h5>
                <p class="text-muted">AI-generated narratives summarize market impact across currencies, indices, commodities, and macro events.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <h5>Data-Driven Research</h5>
                <p class="text-muted">Combine economic calendar signals, geopolitical events, and market intelligence in one interface.</p>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <h2 class="mb-3">Designed for financial teams</h2>
        <p class="text-muted">FinIntel.AI is built to support trading desks, research analysts, and investor communications teams with a unified, AI-enhanced content platform. The system is optimized for signal discovery, fast decision support, and high-quality editorial presentation.</p>
    </div>
</div>
@endsection
