<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\EnsureUserHasRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new \App\Jobs\CollectNewsJob)->everyFifteenMinutes();
        $schedule->job(new \App\Jobs\AnalyzeNewsJob)->hourly();
        $schedule->job(new \App\Jobs\GenerateArticlesJob)->everyTwoHours();
        $schedule->job(new \App\Jobs\UpdateSitemapJob)->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'sanitize.inputs' => \App\Http\Middleware\SanitizeRequestInputs::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
