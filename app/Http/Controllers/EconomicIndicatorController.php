<?php

namespace App\Http\Controllers;

use App\Models\EconomicEvent;
use App\Models\EconomicIndicator;
use App\Services\EconomicEventService;
use App\Jobs\ProcessEconomicEventJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EconomicIndicatorController extends Controller
{
    public function __construct(protected EconomicEventService $eventService) {}

    /**
     * Display the economic event processing dashboard.
     */
    public function index()
    {
        $summary = $this->eventService->getDashboardSummary();
        $indicators = EconomicIndicator::withCount('economicEvents')->orderBy('category')->orderBy('name')->get();
        $latestByIndicator = $this->eventService->getLatestByIndicator();

        return view('admin.indicators.index', compact('summary', 'indicators', 'latestByIndicator'));
    }

    /**
     * Show details for a specific indicator type.
     */
    public function show(string $code)
    {
        if (!isset(EconomicIndicator::INDICATORS[$code])) {
            abort(404, "Unknown indicator: {$code}");
        }

        $indicatorDef = EconomicIndicator::INDICATORS[$code];
        $indicator = EconomicIndicator::where('code', $code)->first();

        $events = EconomicEvent::byIndicator($code)
            ->with('marketImpact')
            ->orderBy('release_time', 'desc')
            ->paginate(20);

        $stats = [
            'total' => EconomicEvent::byIndicator($code)->count(),
            'beats' => EconomicEvent::byIndicator($code)->beat()->count(),
            'misses' => EconomicEvent::byIndicator($code)->miss()->count(),
            'inline' => EconomicEvent::byIndicator($code)->inline()->count(),
            'avg_surprise' => EconomicEvent::byIndicator($code)
                ->whereNotNull('surprise')
                ->avg('surprise'),
        ];

        return view('admin.indicators.show', compact('code', 'indicatorDef', 'indicator', 'events', 'stats'));
    }

    /**
     * Process all pending events (classify + compute surprise).
     */
    public function processPending()
    {
        $stats = $this->eventService->processPendingEvents();

        return redirect()->route('admin.indicators.index')
            ->with('success', "Processed {$stats['processed']} events. Classified: {$stats['classified']}, Surprises: {$stats['surprises']}, Errors: {$stats['errors']}.");
    }

    /**
     * Classify all unclassified events.
     */
    public function classifyAll()
    {
        $count = $this->eventService->classifyAll();

        return redirect()->route('admin.indicators.index')
            ->with('success', "Classified {$count} events by indicator type.");
    }

    /**
     * Recompute surprise for all events missing surprise data.
     */
    public function recomputeSurprises()
    {
        $count = $this->eventService->recomputeSurprises();

        return redirect()->route('admin.indicators.index')
            ->with('success', "Recomputed surprise for {$count} events.");
    }

    /**
     * Seed the 9 default indicator definitions.
     */
    public function seedIndicators()
    {
        $count = EconomicIndicator::seedDefaults();

        return redirect()->route('admin.indicators.index')
            ->with('success', "{$count} new indicator definitions seeded.");
    }

    /**
     * Process a single economic event.
     */
    public function processEvent(EconomicEvent $event)
    {
        try {
            $this->eventService->processEvent($event);

            $type = $event->indicator_type ?? 'unclassified';
            $surprise = $event->surprise_label;

            return redirect()->route('admin.indicators.index')
                ->with('success', "Event #{$event->id} processed: {$type}, {$surprise}.");
        } catch (\Exception $e) {
            return redirect()->route('admin.indicators.index')
                ->with('error', "Failed to process event #{$event->id}: " . $e->getMessage());
        }
    }
}
