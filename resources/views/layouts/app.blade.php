<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI Finance Intelligence Platform')</title>
    <meta name="description" content="@yield('meta_description', 'Enterprise-grade financial analytics and market impact reports powered by AI.')">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Style Sheet -->
    <style>
        :root {
            --bg-main: #0a0d14;
            --bg-card: #111622;
            --bg-border: #1d2436;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --bullish-color: #10b981;
            --bearish-color: #ef4444;
            --neutral-color: #64748b;
            --accent-primary: #2563eb;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--bg-border);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--text-primary) !important;
            letter-spacing: -0.5px;
        }

        .nav-link {
            color: var(--text-secondary) !important;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--text-primary) !important;
        }

        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--bg-border);
            border-radius: 12px;
            color: var(--text-primary);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            border-color: var(--accent-primary);
        }

        .card-title {
            font-weight: 600;
            line-height: 1.4;
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        .badge-bullish {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--bullish-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-bearish {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--bearish-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .badge-neutral {
            background-color: rgba(100, 116, 139, 0.1);
            color: var(--neutral-color);
            border: 1px solid rgba(100, 116, 139, 0.2);
        }

        .sentiment-bar {
            height: 8px;
            border-radius: 4px;
            background-color: var(--bg-border);
            overflow: hidden;
        }

        .form-control, .form-select {
            background-color: #0f172a;
            border-color: var(--bg-border);
            color: var(--text-primary);
        }

        .form-control:focus, .form-select:focus {
            background-color: #0f172a;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.2);
            color: var(--text-primary);
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .auth-panel {
            max-width: 460px;
            margin: 0 auto;
        }

        footer {
            background-color: var(--bg-card);
            border-top: 1px solid var(--bg-border);
            padding: 2rem 0;
            margin-top: auto;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg-main);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--bg-border);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-primary);
        }
    </style>
    @yield('styles')
</head>
<body>

    <nav class="navbar navbar-expand-lg py-3 sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
                <i class="bi bi-cpu text-primary me-2 fs-4"></i>
                <span>FinIntel<span class="text-primary">.AI</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item me-3">
                        <a class="nav-link {{ Route::is('home') ? 'active' : '' }}" href="{{ route('home') }}">Intelligence Feed</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link {{ Route::is('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">Categories</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link {{ Route::is('market-analysis.*') ? 'active' : '' }}" href="{{ route('market-analysis.index') }}">Market Analysis</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link {{ Route::is('calendar.*') ? 'active' : '' }}" href="{{ route('calendar.index') }}">Economic Calendar</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link {{ Route::is('geopolitical.*') ? 'active' : '' }}" href="{{ route('geopolitical.index') }}">Geopolitical</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link {{ Route::is('search.*') ? 'active' : '' }}" href="{{ route('search.index') }}">Search</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link {{ Route::is('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link {{ Route::is('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a>
                    </li>
                    @auth
                        <li class="nav-item me-3">
                            <a class="nav-link {{ Route::is('dashboard') || Route::is('editor.*') || Route::is('admin.*') ? 'active' : '' }}" href="{{ route(auth()->user()->redirectRouteName()) }}">Dashboard</a>
                        </li>
                        @if(auth()->user()->hasRole('admin'))
                            <li class="nav-item me-3">
                                <a class="nav-link {{ Route::is('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">System Monitor</a>
                            </li>
                            <li class="nav-item me-3">
                                <a class="nav-link {{ Route::is('admin.ingestion.*') ? 'active' : '' }}" href="{{ route('admin.ingestion.index') }}">Ingestion</a>
                            </li>
                            <li class="nav-item me-3">
                                <a class="nav-link {{ Route::is('admin.indicators.*') ? 'active' : '' }}" href="{{ route('admin.indicators.index') }}">Indicators</a>
                            </li>
                            <li class="nav-item me-3">
                                <a class="nav-link {{ Route::is('admin.geopolitical.*') ? 'active' : '' }}" href="{{ route('admin.geopolitical.dashboard') }}">Geopolitical</a>
                            </li>
                            <li class="nav-item me-3">
                                <a class="nav-link {{ Route::is('admin.news-sources.*') ? 'active' : '' }}" href="{{ route('admin.news-sources.index') }}">Sources</a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm px-3 rounded-pill">Logout</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item me-3">
                            <a class="nav-link {{ Route::is('login') ? 'active' : '' }}" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary btn-sm px-3 rounded-pill" href="{{ route('register') }}">Register</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        @yield('content')
    </main>

    <footer>
        <div class="container text-center">
            <p class="mb-1 text-muted">&copy; 2026 FinIntel.AI. All rights reserved.</p>
            <small class="text-muted d-block">Enterprise-grade financial data parsing & DALL-E/GPT synthesis pipeline. Operating under automated scheduler configurations.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @yield('scripts')
</body>
</html>
