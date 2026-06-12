@extends('layouts.app')

@section('title', 'Login | FinIntel.AI')

@section('content')
<div class="container">
    <div class="card auth-panel">
        <div class="card-body p-4">
            <h1 class="h3 mb-1">Login</h1>
            <p class="text-muted mb-4">Access your financial intelligence workspace.</p>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus autocomplete="username">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input id="remember" name="remember" type="checkbox" class="form-check-input">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="link-primary text-decoration-none">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <p class="text-muted mb-0 mt-4 text-center">
                Need an account? <a href="{{ route('register') }}" class="link-primary text-decoration-none">Register</a>
            </p>
        </div>
    </div>
</div>
@endsection
