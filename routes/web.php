<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryPageController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EconomicIndicatorController;
use App\Http\Controllers\GeopoliticalEventController;
use App\Http\Controllers\IngestionController;
use App\Http\Controllers\MarketAnalysisController;
use App\Http\Controllers\NewsSourceController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SearchController;

Route::middleware(['security.headers', 'sanitize.inputs'])->group(function () {
    require __DIR__.'/auth.php';

    Route::get('/', [ArticleController::class, 'index'])->name('home');
    Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/{event}', [CalendarController::class, 'show'])->name('calendar.show');
    Route::get('/categories', [CategoryPageController::class, 'index'])->name('categories.index');
    Route::get('/categories/{slug}', [CategoryPageController::class, 'show'])->name('categories.show');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/market-analysis', [MarketAnalysisController::class, 'index'])->name('market-analysis.index');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/contact', [PageController::class, 'submitContact'])->middleware('throttle:10,1')->name('contact.send');

    // Geopolitical Risk Monitor (public)
    Route::get('/geopolitical', [GeopoliticalEventController::class, 'index'])->name('geopolitical.index');
    Route::get('/geopolitical/{event}', [GeopoliticalEventController::class, 'show'])->name('geopolitical.show');

    Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::view('/editor/dashboard', 'dashboard.editor')
        ->middleware(['role:admin,editor', 'throttle:30,1'])
        ->name('editor.dashboard');

    Route::middleware(['role:admin', 'throttle:60,1'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('categories', CategoryController::class);
        Route::resource('news-sources', NewsSourceController::class);
        Route::post('/seed-sources', [DashboardController::class, 'seedSources'])->name('seed-sources');
        Route::post('/trigger-ingest', [DashboardController::class, 'triggerIngest'])->name('trigger-ingest');
        Route::post('/trigger-analysis', [DashboardController::class, 'triggerAnalysis'])->name('trigger-analysis');

        // Ingestion Engine
        Route::get('/ingestion', [IngestionController::class, 'index'])->name('ingestion.index');
        Route::post('/ingestion/trigger-all', [IngestionController::class, 'triggerAll'])->name('ingestion.trigger-all');
        Route::post('/ingestion/dispatch-all', [IngestionController::class, 'dispatchAllToQueue'])->name('ingestion.dispatch-all');
        Route::post('/ingestion/trigger/{type}', [IngestionController::class, 'triggerByType'])->name('ingestion.trigger-type');
        Route::post('/ingestion/source/{news_source}', [IngestionController::class, 'triggerSource'])->name('ingestion.trigger-source');
        Route::get('/ingestion/log/{ingestionLog}', [IngestionController::class, 'showLog'])->name('ingestion.show-log');
        Route::post('/ingestion/seed-sources', [IngestionController::class, 'seedSources'])->name('ingestion.seed-sources');

        // Economic Indicators
        Route::get('/indicators', [EconomicIndicatorController::class, 'index'])->name('indicators.index');
        Route::get('/indicators/{code}', [EconomicIndicatorController::class, 'show'])->name('indicators.show');
        Route::post('/indicators/process-pending', [EconomicIndicatorController::class, 'processPending'])->name('indicators.process-pending');
        Route::post('/indicators/classify-all', [EconomicIndicatorController::class, 'classifyAll'])->name('indicators.classify-all');
        Route::post('/indicators/recompute-surprises', [EconomicIndicatorController::class, 'recomputeSurprises'])->name('indicators.recompute-surprises');
        Route::post('/indicators/seed', [EconomicIndicatorController::class, 'seedIndicators'])->name('indicators.seed');
        Route::post('/indicators/event/{event}', [EconomicIndicatorController::class, 'processEvent'])->name('indicators.process-event');

        // Geopolitical Risk Analysis
        Route::get('/geopolitical', [GeopoliticalEventController::class, 'dashboard'])->name('geopolitical.dashboard');
        Route::get('/geopolitical/type/{code}', [GeopoliticalEventController::class, 'showType'])->name('geopolitical.type');
        Route::post('/geopolitical/process-pending', [GeopoliticalEventController::class, 'processPending'])->name('geopolitical.process-pending');
        Route::post('/geopolitical/classify-all', [GeopoliticalEventController::class, 'classifyAll'])->name('geopolitical.classify-all');
        Route::post('/geopolitical/detect-regions', [GeopoliticalEventController::class, 'detectRegions'])->name('geopolitical.detect-regions');
        Route::post('/geopolitical/detect-escalations', [GeopoliticalEventController::class, 'detectEscalations'])->name('geopolitical.detect-escalations');
        Route::post('/geopolitical/seed-types', [GeopoliticalEventController::class, 'seedTypes'])->name('geopolitical.seed-types');
        Route::post('/geopolitical/event/{event}/process', [GeopoliticalEventController::class, 'processEvent'])->name('geopolitical.process-event');
        Route::post('/geopolitical/event/{event}/escalate', [GeopoliticalEventController::class, 'escalateEvent'])->name('geopolitical.escalate-event');
        Route::post('/geopolitical/event/{event}/resolve', [GeopoliticalEventController::class, 'resolveEvent'])->name('geopolitical.resolve-event');
        });
    });
});
