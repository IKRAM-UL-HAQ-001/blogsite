<?php

namespace App\Jobs;

use App\Models\EconomicEvent;
use App\Services\EconomicEventService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEconomicEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(
        protected EconomicEvent $economicEvent
    ) {
        $this->onQueue('processing');
    }

    public function handle(EconomicEventService $eventService): void
    {
        Log::info("ProcessEconomicEventJob started for event #{$this->economicEvent->id}: {$this->economicEvent->event_name}");

        $eventService->processEvent($this->economicEvent);

        Log::info("ProcessEconomicEventJob completed for event #{$this->economicEvent->id}", [
            'indicator_type' => $this->economicEvent->indicator_type,
            'surprise_direction' => $this->economicEvent->surprise_direction,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessEconomicEventJob failed for event #{$this->economicEvent->id}: " . $exception->getMessage());

        $this->economicEvent->update(['status' => 'failed']);
    }
}
