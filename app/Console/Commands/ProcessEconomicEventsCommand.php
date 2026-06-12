<?php

namespace App\Console\Commands;

use App\Services\EconomicEventService;
use App\Models\EconomicIndicator;
use Illuminate\Console\Command;

class ProcessEconomicEventsCommand extends Command
{
    protected $signature = 'events:process
                            {--classify : Only classify indicator types}
                            {--surprises : Only recompute surprises}
                            {--seed : Seed indicator definitions}';

    protected $description = 'Process economic events: classify indicators, compute surprises, trigger analysis';

    public function handle(EconomicEventService $eventService): int
    {
        if ($this->option('seed')) {
            $count = EconomicIndicator::seedDefaults();
            $this->info("Seeded {$count} indicator definitions.");
            return self::SUCCESS;
        }

        if ($this->option('classify')) {
            $count = $eventService->classifyAll();
            $this->info("Classified {$count} events by indicator type.");
            return self::SUCCESS;
        }

        if ($this->option('surprises')) {
            $count = $eventService->recomputeSurprises();
            $this->info("Recomputed surprise for {$count} events.");
            return self::SUCCESS;
        }

        // Full processing
        $this->info('Processing all pending economic events...');

        $stats = $eventService->processPendingEvents();

        $this->info('');
        $this->info('=== Processing Results ===');
        $this->line("  Processed: {$stats['processed']}");
        $this->line("  Classified: {$stats['classified']}");
        $this->line("  Surprises: {$stats['surprises']}");
        $this->line("  Analysis dispatched: {$stats['analysis_dispatched']}");
        $this->line("  Errors: {$stats['errors']}");

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
