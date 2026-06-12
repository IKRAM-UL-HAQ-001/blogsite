<?php

namespace App\Jobs;

use App\Models\GeopoliticalEvent;
use App\Services\GeopoliticalEventService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGeopoliticalEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    protected int $eventId;

    public function __construct(GeopoliticalEvent $event)
    {
        $this->eventId = $event->id;
        $this->onQueue('processing');
    }

    public function handle(GeopoliticalEventService $service): void
    {
        $event = GeopoliticalEvent::find($this->eventId);

        if (!$event) {
            Log::warning("ProcessGeopoliticalEventJob: Event ID {$this->eventId} not found.");
            return;
        }

        Log::info("Processing geopolitical event ID {$event->id}: {$event->title}");

        $service->processEvent($event);

        Log::info("Geopolitical event ID {$event->id} processed. Type: {$event->event_type}, Severity: {$event->severity}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessGeopoliticalEventJob failed for event ID {$this->eventId}: " . $exception->getMessage());

        $event = GeopoliticalEvent::find($this->eventId);
        if ($event) {
            $event->update(['status' => 'failed']);
        }
    }
}
