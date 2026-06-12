@extends('layouts.app')

@section('title', 'Edit Category - FinIntel.AI')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-2">Blog Categories</div>
            <h1 class="h2 fw-bold text-white mb-1">Edit Category</h1>
            <p class="text-muted mb-0">Update category naming, hierarchy, and editorial notes.</p>
        </div>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card category-form-card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                @method('PUT')
                @include('admin.categories._form', ['buttonText' => 'Update Category'])
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
@include('admin.categories._styles')
@endsection
