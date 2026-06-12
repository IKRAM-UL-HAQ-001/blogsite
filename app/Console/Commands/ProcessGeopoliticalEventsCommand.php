<?php

namespace App\Console\Commands;

use App\Models\GeopoliticalEvent;
use App\Models\GeopoliticalEventType;
use App\Services\GeopoliticalEventService;
use Illuminate\Console\Command;

class ProcessGeopoliticalEventsCommand extends Command
{
    protected $signature = 'events:geopolitical
                            {--classify : Only classify event types}
                            {--regions : Only detect regions}
                            {--escalate : Detect and apply escalation patterns}
                            {--seed : Seed 7 default event types}';

    protected $description = 'Process geopolitical events: classify, detect regions, dispatch AI analysis';

    public function handle(GeopoliticalEventService $service): int
    {
        if ($this->option('seed')) {
            $count = GeopoliticalEventType::seedDefaults();
            $this->info("Seeded {$count} geopolitical event types.");
            return 0;
        }

        if ($this->option('classify')) {
            $result = $service->classifyAll();
            $this->info("Classified {$result['classified']} of {$result['total']} unclassified events.");
            return 0;
        }

        if ($this->option('regions')) {
            $result = $service->detectAllRegions();
            $this->info("Detected regions for {$result['regions_detected']} of {$result['total']} events.");
            return 0;
        }

        if ($this->option('escalate')) {
            $escalated = $service->detectEscalations();
            $count = count($escalated);
            $this->info("Auto-escalated {$count} events based on frequency patterns.");
            return 0;
        }

        // Default: full processing pipeline
        $this->info('Processing pending geopolitical events...');

        $stats = $service->processPendingEvents();

        $this->info("Total pending: {$stats['total']}");
        $this->info("Classified: {$stats['classified']}");
        $this->info("AI analysis dispatched: {$stats['analyzed_dispatched']}");
        $this->info("Errors: {$stats['errors']}");

        // Also detect escalations
        $escalated = $service->detectEscalations();
        if (count($escalated) > 0) {
            $this->warn("Auto-escalated " . count($escalated) . " events based on frequency patterns.");
        }

        return 0;
    }
}
