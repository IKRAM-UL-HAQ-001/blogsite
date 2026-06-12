@extends('layouts.app')

@section('title', 'Contact FinIntel.AI - Request AI Market Intelligence')
@section('meta_description', 'Reach out to the FinIntel.AI team for platform inquiries, integration requests, or research collaboration.')

@section('content')
<div class="container py-4">
    <div class="row align-items-center mb-5">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold">Contact the Team</h1>
            <p class="lead text-muted">Send a message to discuss platform access, data integrations, or financial intelligence workflows.</p>
        </div>
        <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
            <a href="mailto:info@finintel.ai" class="btn btn-primary">Email Support</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="mb-3">Get in touch</h5>
                <p class="text-muted">Fill out the form and our team will follow up with platform details, partnership opportunities, and technical support resources.</p>
                <ul class="list-unstyled text-muted">
                    <li class="mb-3"><i class="bi bi-geo-alt-fill text-primary me-2"></i> Global analytics HQ</li>
                    <li class="mb-3"><i class="bi bi-envelope-fill text-primary me-2"></i> info@finintel.ai</li>
                    <li class="mb-3"><i class="bi bi-phone-fill text-primary me-2"></i> +1 (555) 010-2030</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4">
                <form method="POST" action="{{ route('contact.send') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
