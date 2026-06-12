<?php

namespace App\Http\Controllers;

use App\Models\EconomicEvent;
use App\Models\EconomicIndicator;
use App\Services\EconomicEventService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $query = EconomicEvent::with(['marketImpact', 'indicator'])
            ->orderBy('release_time', 'desc');

        // Filter by indicator type
        if ($request->filled('indicator')) {
            $query->where('indicator_type', $request->input('indicator'));
        }

        // Filter by importance
        if ($request->filled('importance')) {
            $query->where('importance', $request->input('importance'));
        }

        // Filter by country
        if ($request->filled('country')) {
            $query->where('country', strtoupper($request->input('country')));
        }

        // Filter by surprise direction
        if ($request->filled('surprise')) {
            $query->where('surprise_direction', $request->input('surprise'));
        }

        // Filter by category (through indicator relationship)
        if ($request->filled('category')) {
            $indicatorCodes = EconomicIndicator::where('category', $request->input('category'))
                ->pluck('code')
                ->toArray();
            $query->whereIn('indicator_type', $indicatorCodes);
        }

        // Time range filter
        if ($request->filled('period')) {
            switch ($request->input('period')) {
                case 'today':
                    $query->today();
                    break;
                case 'week':
                    $query->recent(7);
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
            }
        }

        $events = $query->paginate(20)->withQueryString();

        // Indicator list for filter dropdown
        $indicators = EconomicIndicator::INDICATORS;
        $categories = EconomicIndicator::CATEGORIES;

        return view('calendar.index', compact('events', 'indicators', 'categories'));
    }

    /**
     * Show a single economic event with detailed analysis.
     */
    public function show(EconomicEvent $event)
    {
        $event->load(['marketImpact', 'indicator']);

        $previousRelease = $event->previousRelease();

        return view('calendar.show', compact('event', 'previousRelease'));
    }
}
