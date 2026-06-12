<?php

namespace App\Http\Controllers;

use App\Models\GeopoliticalEvent;
use App\Models\GeopoliticalEventType;
use App\Services\GeopoliticalEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GeopoliticalEventController extends Controller
{
    public function __construct(
        protected GeopoliticalEventService $geoService
    ) {}

    /**
     * Public geopolitical events listing with filters.
     */
    public function index(Request $request)
    {
        $query = GeopoliticalEvent::with(['eventType', 'rawArticle'])->active()->recent(30);

        // Filters
        if ($request->filled('type')) {
            $query->byType($request->type);
        }
        if ($request->filled('severity')) {
            $query->bySeverity($request->severity);
        }
        if ($request->filled('region')) {
            $query->byRegion($request->region);
        }
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $events = $query->paginate(20)->withQueryString();

        return view('geopolitical.index', [
            'events' => $events,
            'eventTypes' => GeopoliticalEventType::EVENT_TYPES,
            'categories' => GeopoliticalEventType::CATEGORIES,
            'regions' => GeopoliticalEventType::REGIONS,
            'filters' => $request->only(['type', 'severity', 'region', 'category', 'status']),
        ]);
    }

    /**
     * Show a single geopolitical event detail.
     */
    public function show(GeopoliticalEvent $event)
    {
        $event->load(['eventType', 'rawArticle', 'parentEvent', 'childEvents', 'marketImpact']);

        // Get related events of the same type or region
        $relatedEvents = GeopoliticalEvent::where('id', '!=', $event->id)
            ->where(function ($q) use ($event) {
                $q->where('event_type', $event->event_type)
                  ->orWhere('region', $event->region);
            })
            ->active()
            ->orderBy('occurred_at', 'desc')
            ->limit(5)
            ->get();

        return view('geopolitical.show', [
            'event' => $event,
            'relatedEvents' => $relatedEvents,
            'eventTypes' => GeopoliticalEventType::EVENT_TYPES,
            'regions' => GeopoliticalEventType::REGIONS,
        ]);
    }

    /**
     * Admin dashboard for geopolitical risk monitoring.
     */
    public function dashboard()
    {
        $summary = $this->geoService->getDashboardSummary();
        $latestByType = $this->geoService->getLatestByType();

        return view('admin.geopolitical.index', [
            'summary' => $summary,
            'latestByType' => $latestByType,
            'eventTypes' => GeopoliticalEventType::EVENT_TYPES,
            'categories' => GeopoliticalEventType::CATEGORIES,
            'regions' => GeopoliticalEventType::REGIONS,
        ]);
    }

    /**
     * Show a specific event type's events.
     */
    public function showType(string $code)
    {
        $typeDef = GeopoliticalEventType::EVENT_TYPES[$code] ?? null;
        if (!$typeDef) {
            abort(404, "Unknown event type: {$code}");
        }

        $events = GeopoliticalEvent::byType($code)
            ->with(['eventType', 'rawArticle'])
            ->orderBy('occurred_at', 'desc')
            ->paginate(20);

        return view('admin.geopolitical.type', [
            'code' => $code,
            'typeDef' => $typeDef,
            'events' => $events,
        ]);
    }

    /**
     * Process all pending geopolitical events.
     */
    public function processPending()
    {
        $stats = $this->geoService->processPendingEvents();

        return Redirect::route('admin.geopolitical.dashboard')
            ->with('status', "Processed {$stats['total']} events: {$stats['classified']} classified, {$stats['analyzed_dispatched']} analysis dispatched, {$stats['errors']} errors");
    }

    /**
     * Classify all unclassified events.
     */
    public function classifyAll()
    {
        $result = $this->geoService->classifyAll();

        return Redirect::route('admin.geopolitical.dashboard')
            ->with('status', "Classified {$result['classified']} of {$result['total']} unclassified events");
    }

    /**
     * Detect regions for all events without regions.
     */
    public function detectRegions()
    {
        $result = $this->geoService->detectAllRegions();

        return Redirect::route('admin.geopolitical.dashboard')
            ->with('status', "Detected regions for {$result['regions_detected']} of {$result['total']} events");
    }

    /**
     * Seed the 7 default geopolitical event types.
     */
    public function seedTypes()
    {
        $count = GeopoliticalEventType::seedDefaults();

        return Redirect::route('admin.geopolitical.dashboard')
            ->with('status', "Seeded {$count} geopolitical event types");
    }

    /**
     * Process a single event.
     */
    public function processEvent(GeopoliticalEvent $event)
    {
        $this->geoService->processEvent($event);

        return Redirect::back()
            ->with('status', "Event #{$event->id} processed: type={$event->event_type}, severity={$event->severity}");
    }

    /**
     * Escalate a single event.
     */
    public function escalateEvent(GeopoliticalEvent $event, Request $request)
    {
        $level = $request->input('level');
        $event->escalate($level);

        return Redirect::back()
            ->with('status', "Event #{$event->id} escalated to level {$event->escalation_level} ({$event->escalation_label})");
    }

    /**
     * Resolve a single event.
     */
    public function resolveEvent(GeopoliticalEvent $event)
    {
        $event->resolve();

        return Redirect::back()
            ->with('status', "Event #{$event->id} resolved");
    }

    /**
     * Detect escalation patterns.
     */
    public function detectEscalations()
    {
        $escalated = $this->geoService->detectEscalations();
        $count = count($escalated);

        return Redirect::route('admin.geopolitical.dashboard')
            ->with('status', "Auto-escalated {$count} events based on frequency patterns");
    }
}
