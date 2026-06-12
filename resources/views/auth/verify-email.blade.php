@extends('layouts.app')

@section('title', 'Verify Email | FinIntel.AI')

@section('content')
<div class="container">
    <div class="card auth-panel">
        <div class="card-body p-4">
            <h1 class="h3 mb-1">Verify Email</h1>
            <p class="text-muted mb-4">Before continuing, please verify your email address using the link we sent after registration.</p>

            @if (session('status') === 'verification-link-sent')
                <div class="alert alert-success">A new verification link has been sent to your email address.</div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
                @csrf
                <button type="submit" class="btn btn-primary w-100">Resend Verification Email</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary w-100">Logout</button>
            </form>
        </div>
    </div>
</div>
@endsection
