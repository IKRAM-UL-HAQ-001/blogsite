@extends('layouts.app')

@section('title', 'Edit News Source - FinIntel.AI')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-2">Source Management</div>
            <h1 class="h2 fw-bold text-white mb-1">Edit News Source</h1>
            <p class="text-muted mb-0">Update source configuration, classification, or availability.</p>
        </div>
        <a href="{{ route('admin.news-sources.index') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card admin-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.news-sources.update', $source) }}">
                @method('PUT')
                @include('admin.news-sources._form', ['buttonText' => 'Update Source'])
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
@include('admin.news-sources._styles')
@endsection
