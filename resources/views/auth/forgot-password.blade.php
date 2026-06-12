@extends('layouts.app')

@section('title', 'Reset Password | FinIntel.AI')

@section('content')
<div class="container">
    <div class="card auth-panel">
        <div class="card-body p-4">
            <h1 class="h3 mb-1">Reset Password</h1>
            <p class="text-muted mb-4">Enter your email and we will send a password reset link.</p>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus autocomplete="username">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">Email Password Reset Link</button>
            </form>
        </div>
    </div>
</div>
@endsection
