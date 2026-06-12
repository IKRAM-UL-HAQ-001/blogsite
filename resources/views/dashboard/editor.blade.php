@extends('layouts.app')

@section('title', 'Editor Dashboard | FinIntel.AI')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-body p-4">
            <h1 class="h3 mb-2">Editor Dashboard</h1>
            <p class="text-muted mb-0">Welcome, {{ auth()->user()->name }}. Editor access is enabled for content workflows.</p>
        </div>
    </div>
</div>
@endsection
