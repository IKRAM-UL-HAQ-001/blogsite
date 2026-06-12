<?php

namespace App\Jobs\Calendar;

use App\Models\EconomicEvent;
use App\Models\EconomicIndicator;
use App\Models\PipelineRun;
use App\Services\Providers\Calendar\ForexFactoryCalendarProvider;
use App\Services\Providers\Calendar\ManualCalendarProvider;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Syncs upcoming economic calendar events from external providers.
 * Runs every 6 hours and looks 14 days ahead by default.
 *
 * Provider priority:
 *   1. ForexFactoryCalendarProvider (community JSON mirror — fragile)
 *   2. ManualCalendarProvider       (fallback / seed data)
 */
final class SyncEconomicCalendarJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 3;
    public int $timeout   = 300;
    public int $uniqueFor = 21600; // 6 hours

    public function __construct(public readonly int $daysAhead = 14)
    {
        $this->onQueue('ingestion');
    }

    public function handle(): void
    {
        $from = CarbonImmutable::now();
        $to   = CarbonImmutable::now()->addDays($this->daysAhead);

        $run = PipelineRun::start('sync_economic_calendar', [
            'days_ahead' => $this->daysAhead,
            'range_from' => $from->toDateTimeString(),
            'range_to'   => $to->toDateTimeString(),
        ]);

        $providers = [
            new ForexFactoryCalendarProvider(),
            new ManualCalendarProvider(),
        ];

        $total      = 0;
        $stored     = 0;
        $duplicates = 0;
        $errors     = 0;

        foreach ($providers as $provider) {
            try {
                $events = $provider->fetch($from, $to);

                foreach ($events as $event) {
                    $total++;

                    // Duplicate: same event name within ±30-minute window
                    $isDuplicate = EconomicEvent::where('event_name', $event->eventName)
                        ->whereBetween('release_time', [
                            $event->scheduledAt->subMinutes(30),
                            $event->scheduledAt->addMinutes(30),
                        ])
                        ->exists();

                    if ($isDuplicate) {
                        $duplicates++;
                        continue;
                    }

                    $currency      = strtoupper($event->currency ?: $event->country);
                    $indicatorType = EconomicIndicator::classify($event->eventName);

                    // Upgrade importance if the indicator registry has a default
                    $importance = $event->importance;
                    if ($indicatorType && isset(EconomicIndicator::INDICATORS[$indicatorType])) {
                        $def = EconomicIndicator::INDICATORS[$indicatorType];
                        if ($importance === 'low') {
                            $importance = $def['default_importance'] ?? $importance;
                        }
                    }

                    $economicEvent = EconomicEvent::create([
                        'event_name'     => $event->eventName,
                        'indicator_type' => $indicatorType,
                        'country'        => $currency,
                        'actual'         => $event->actual,
                        'forecast'       => $event->forecast,
                        'previous'       => $event->previous,
                        'importance'     => $importance,
                        'release_time'   => $event->scheduledAt,
                        'status'         => 'pending',
                    ]);

                    $economicEvent->computeSurprise();
                    $economicEvent->save();

                    $stored++;
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::error('SyncEconomicCalendarJob provider error: ' . $e->getMessage());
            }
        }

        $run->complete($total, $stored, $errors, ['duplicates' => $duplicates]);

        Log::info("SyncEconomicCalendarJob: stored {$stored}, duplicates {$duplicates}, errors {$errors}.");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncEconomicCalendarJob failed: ' . $e->getMessage());
    }
}
