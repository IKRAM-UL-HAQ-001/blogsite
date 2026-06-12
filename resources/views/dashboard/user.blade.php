@extends('layouts.app')

@section('title', 'Dashboard | FinIntel.AI')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body p-4">
            <h1 class="h3 mb-2">User Dashboard</h1>
            <p class="text-muted mb-0">Welcome, {{ auth()->user()->name }}. Your account is verified and ready.</p>
        </div>
    </div>
</div>
@endsection
