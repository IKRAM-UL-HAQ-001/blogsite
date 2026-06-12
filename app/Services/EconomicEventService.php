<?php

namespace App\Services;

use App\Models\EconomicEvent;
use App\Models\EconomicIndicator;
use App\Models\IngestionLog;
use App\Jobs\AnalyzeMarketImpactJob;
use Illuminate\Support\Facades\Log;

class EconomicEventService
{
    /**
     * Process all pending economic events: classify, compute surprise, trigger analysis.
     */
    public function processPendingEvents(): array
    {
        $pending = EconomicEvent::where('status', 'pending')->get();
        $stats = [
            'processed' => 0,
            'classified' => 0,
            'surprises' => 0,
            'analysis_dispatched' => 0,
            'errors' => 0,
        ];

        foreach ($pending as $event) {
            try {
                $this->processEvent($event);
                $stats['processed']++;

                if ($event->indicator_type) {
                    $stats['classified']++;
                }
                if ($event->surprise_direction && $event->surprise_direction !== 'inline') {
                    $stats['surprises']++;
                }
                if ($event->status === 'analyzed' || $event->importance === 'high') {
                    $stats['analysis_dispatched']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Failed to process EconomicEvent #{$event->id}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Process a single economic event: classify indicator type, compute surprise,
     * and dispatch AI analysis for high-impact events.
     */
    public function processEvent(EconomicEvent $event): EconomicEvent
    {
        // Step 1: Classify indicator type
        if (empty($event->indicator_type)) {
            $event->indicator_type = EconomicIndicator::classify($event->event_name);
        }

        // Step 2: Override importance from indicator defaults if available
        if ($event->indicator_type && isset(EconomicIndicator::INDICATORS[$event->indicator_type])) {
            $indicatorDef = EconomicIndicator::INDICATORS[$event->indicator_type];
            // Only override if it's currently the default 'low'
            if ($event->importance === 'low') {
                $event->importance = $indicatorDef['default_importance'];
            }
        }

        // Step 3: Compute surprise (actual vs forecast)
        $event->computeSurprise();

        // Step 4: Save classified + computed data
        $event->save();

        Log::info("Processed EconomicEvent #{$event->id}", [
            'event_name' => $event->event_name,
            'indicator_type' => $event->indicator_type,
            'surprise' => $event->surprise,
            'surprise_direction' => $event->surprise_direction,
        ]);

        // Step 5: Dispatch AI market impact analysis for high-impact or surprising events
        if ($event->importance === 'high' || $event->is_surprise) {
            AnalyzeMarketImpactJob::dispatch(null, $event);
        }

        return $event;
    }

    /**
     * Classify all existing events that don't have an indicator_type.
     */
    public function classifyAll(): int
    {
        $unclassified = EconomicEvent::whereNull('indicator_type')->get();
        $count = 0;

        foreach ($unclassified as $event) {
            $type = EconomicIndicator::classify($event->event_name);
            if ($type) {
                $event->update(['indicator_type' => $type]);
                $count++;
            }
        }

        Log::info("Classified {$count} economic events by indicator type.");
        return $count;
    }

    /**
     * Recompute surprise for all events that have actual + forecast but no surprise.
     */
    public function recomputeSurprises(): int
    {
        $events = EconomicEvent::whereNotNull('actual')
            ->whereNotNull('forecast')
            ->whereNull('surprise')
            ->get();

        $count = 0;
        foreach ($events as $event) {
            $event->computeSurprise();
            $event->save();
            $count++;
        }

        Log::info("Recomputed surprise for {$count} economic events.");
        return $count;
    }

    /**
     * Get a dashboard summary of economic events.
     */
    public function getDashboardSummary(): array
    {
        $today = now()->toDateString();

        return [
            'total_events' => EconomicEvent::count(),
            'total_classified' => EconomicEvent::whereNotNull('indicator_type')->count(),
            'total_unclassified' => EconomicEvent::whereNull('indicator_type')->count(),
            'events_today' => EconomicEvent::today()->count(),
            'high_impact_today' => EconomicEvent::today()->highImpact()->count(),
            'beats' => EconomicEvent::beat()->count(),
            'misses' => EconomicEvent::miss()->count(),
            'inline' => EconomicEvent::inline()->count(),
            'pending_processing' => EconomicEvent::pending()->count(),
            'upcoming_high_impact' => EconomicEvent::upcoming()->highImpact()->count(),

            // Per-indicator breakdown
            'by_indicator' => collect(EconomicIndicator::INDICATORS)->mapWithKeys(function ($def, $code) {
                return [$code => [
                    'name' => $def['name'],
                    'category' => $def['category'],
                    'total' => EconomicEvent::byIndicator($code)->count(),
                    'beats' => EconomicEvent::byIndicator($code)->beat()->count(),
                    'misses' => EconomicEvent::byIndicator($code)->miss()->count(),
                    'latest' => EconomicEvent::byIndicator($code)
                        ->whereNotNull('actual')
                        ->orderBy('release_time', 'desc')
                        ->first(),
                ]];
            })->toArray(),
        ];
    }

    /**
     * Get the latest release for each indicator type.
     */
    public function getLatestByIndicator(): array
    {
        $results = [];

        foreach (EconomicIndicator::INDICATORS as $code => $def) {
            $latest = EconomicEvent::byIndicator($code)
                ->whereNotNull('actual')
                ->orderBy('release_time', 'desc')
                ->first();

            $results[$code] = [
                'indicator' => $def,
                'latest_event' => $latest,
            ];
        }

        return $results;
    }
}
