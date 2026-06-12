<?php

namespace App\Services;

use App\Models\GeopoliticalEvent;
use App\Models\GeopoliticalEventType;
use App\Models\RawArticle;
use App\Jobs\AnalyzeGeopoliticalImpactJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GeopoliticalEventService
{
    /**
     * Process all pending geopolitical events: classify, detect region, dispatch AI analysis.
     */
    public function processPendingEvents(): array
    {
        $events = GeopoliticalEvent::pending()->get();
        $stats = [
            'total' => $events->count(),
            'classified' => 0,
            'analyzed_dispatched' => 0,
            'errors' => 0,
        ];

        foreach ($events as $event) {
            try {
                $result = $this->processEvent($event);
                if ($result->event_type) {
                    $stats['classified']++;
                }
                if ($result->status === 'classified' || $result->status === 'analyzed') {
                    if ($result->is_critical || $result->is_escalating) {
                        $stats['analyzed_dispatched']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error("Failed to process geopolitical event ID {$event->id}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Process a single event: classify, detect region, dispatch AI analysis for high-severity.
     */
    public function processEvent(GeopoliticalEvent $event): GeopoliticalEvent
    {
        // Classify event type
        if (empty($event->event_type)) {
            $event->classify();
        }

        // Detect region
        if (empty($event->region)) {
            $event->detectRegion();
        }

        // Set occurred_at if not set
        if (empty($event->occurred_at)) {
            $event->occurred_at = now();
        }

        // Update status
        if ($event->status === 'pending') {
            $event->status = $event->event_type ? 'classified' : 'pending';
        }

        $event->save();

        // Dispatch AI analysis for high-severity or escalating events
        if ($event->is_critical || $event->is_escalating || in_array($event->severity, ['high', 'critical'])) {
            AnalyzeGeopoliticalImpactJob::dispatch($event);
            Log::info("Dispatched AI analysis for geopolitical event ID {$event->id} (type: {$event->event_type}, severity: {$event->severity})");
        }

        return $event;
    }

    /**
     * Classify all unclassified geopolitical events.
     */
    public function classifyAll(): array
    {
        $events = GeopoliticalEvent::whereNull('event_type')
            ->orWhere('event_type', '')
            ->get();

        $classified = 0;
        foreach ($events as $event) {
            $event->classify();
            if ($event->event_type) {
                $classified++;
            }
            $event->save();
        }

        return [
            'total' => $events->count(),
            'classified' => $classified,
        ];
    }

    /**
     * Detect regions for all events without a region.
     */
    public function detectAllRegions(): array
    {
        $events = GeopoliticalEvent::whereNull('region')
            ->orWhere('region', '')
            ->get();

        $detected = 0;
        foreach ($events as $event) {
            $event->detectRegion();
            if ($event->region) {
                $detected++;
            }
            $event->save();
        }

        return [
            'total' => $events->count(),
            'regions_detected' => $detected,
        ];
    }

    /**
     * Create a geopolitical event from a raw article.
     */
    public function createFromArticle(RawArticle $article): ?GeopoliticalEvent
    {
        $text = $article->title . ' ' . ($article->body ?? '');

        // Check if this article is actually geopolitical
        $classification = GeopoliticalEventType::classifyWithConfidence($text);

        if (!$classification['type'] || $classification['confidence'] < 30) {
            return null; // Not a geopolitical event
        }

        // Check for duplicates based on raw_article_id
        $existing = GeopoliticalEvent::where('raw_article_id', $article->id)->first();
        if ($existing) {
            return $existing;
        }

        $typeDef = GeopoliticalEventType::EVENT_TYPES[$classification['type']] ?? null;

        $event = GeopoliticalEvent::create([
            'title' => $article->title,
            'description' => Str::limit($article->body ?? '', 2000),
            'event_type' => $classification['type'],
            'severity' => $typeDef['default_severity'] ?? 'medium',
            'status' => 'classified',
            'raw_article_id' => $article->id,
            'news_source_id' => $article->news_source_id,
            'source_url' => $article->url,
            'occurred_at' => $article->published_at ?? now(),
        ]);

        // Detect region
        $event->detectRegion();
        $event->save();

        // Dispatch AI analysis for high-severity events
        if (in_array($event->severity, ['high', 'critical'])) {
            AnalyzeGeopoliticalImpactJob::dispatch($event);
        }

        return $event;
    }

    /**
     * Get dashboard summary statistics.
     */
    public function getDashboardSummary(): array
    {
        $total = GeopoliticalEvent::count();
        $active = GeopoliticalEvent::active()->count();
        $critical = GeopoliticalEvent::critical()->count();
        $highSeverity = GeopoliticalEvent::highSeverity()->count();
        $escalating = GeopoliticalEvent::escalating()->count();
        $pending = GeopoliticalEvent::pending()->count();
        $analyzed = GeopoliticalEvent::analyzed()->count();
        $today = GeopoliticalEvent::today()->count();

        // By type
        $byType = [];
        foreach (GeopoliticalEventType::EVENT_TYPES as $code => $type) {
            $byType[$code] = [
                'name' => $type['name'],
                'count' => GeopoliticalEvent::byType($code)->count(),
                'active' => GeopoliticalEvent::byType($code)->active()->count(),
                'critical' => GeopoliticalEvent::byType($code)->critical()->count(),
            ];
        }

        // By region
        $byRegion = [];
        foreach (GeopoliticalEventType::REGIONS as $code => $name) {
            $count = GeopoliticalEvent::byRegion($code)->count();
            if ($count > 0) {
                $byRegion[$code] = [
                    'name' => $name,
                    'count' => $count,
                ];
            }
        }

        // By severity
        $bySeverity = [];
        foreach (['critical', 'high', 'medium', 'low'] as $level) {
            $bySeverity[$level] = GeopoliticalEvent::bySeverity($level)->count();
        }

        // Top risk events (unresolved, sorted by risk score)
        $topRiskEvents = GeopoliticalEvent::unresolved()
            ->highSeverity()
            ->orderBy('escalation_level', 'desc')
            ->orderBy('occurred_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total' => $total,
            'active' => $active,
            'critical' => $critical,
            'high_severity' => $highSeverity,
            'escalating' => $escalating,
            'pending' => $pending,
            'analyzed' => $analyzed,
            'today' => $today,
            'by_type' => $byType,
            'by_region' => $byRegion,
            'by_severity' => $bySeverity,
            'top_risk_events' => $topRiskEvents,
        ];
    }

    /**
     * Get latest events for each type.
     */
    public function getLatestByType(): array
    {
        $latest = [];
        foreach (GeopoliticalEventType::EVENT_TYPES as $code => $type) {
            $event = GeopoliticalEvent::byType($code)
                ->orderBy('occurred_at', 'desc')
                ->first();
            $latest[$code] = [
                'type_name' => $type['name'],
                'event' => $event,
            ];
        }
        return $latest;
    }

    /**
     * Check for escalation patterns: events that may be escalating based on frequency.
     */
    public function detectEscalations(): array
    {
        $escalated = [];

        // Events of same type in same region occurring more frequently
        $recentTypes = GeopoliticalEvent::recent(7)
            ->active()
            ->where('escalation_level', '<', 2)
            ->get()
            ->groupBy(function ($event) {
                return $event->event_type . '|' . $event->region;
            });

        foreach ($recentTypes as $key => $events) {
            if ($events->count() >= 3) {
                // 3+ events of same type+region in a week suggests escalation
                foreach ($events as $event) {
                    if ($event->escalation_level < 2) {
                        $event->escalate(2);
                        $escalated[] = $event;
                    }
                }
            }
        }

        return $escalated;
    }
}
