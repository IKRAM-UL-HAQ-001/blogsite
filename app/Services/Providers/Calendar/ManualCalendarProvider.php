<?php

namespace App\Services\Providers\Calendar;

use App\Contracts\EconomicCalendarProvider;
use App\DTOs\NormalizedCalendarEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Seed / fallback provider. Returns a static set of upcoming high-impact events
 * so the pipeline always has data to work with during development and when
 * all upstream calendar feeds are unavailable.
 */
final class ManualCalendarProvider implements EconomicCalendarProvider
{
    public function fetch(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $events = collect();

        foreach ($this->staticEvents() as $raw) {
            try {
                $scheduledAt = CarbonImmutable::parse($raw['date']);
            } catch (\Throwable) {
                continue;
            }

            if ($scheduledAt->isBefore($from) || $scheduledAt->isAfter($to)) {
                continue;
            }

            $events->push(new NormalizedCalendarEvent(
                eventName: $raw['event_name'],
                country: $raw['country'],
                currency: $raw['currency'],
                importance: $raw['importance'],
                scheduledAt: $scheduledAt,
                actual: $raw['actual'] ?? null,
                forecast: $raw['forecast'] ?? null,
                previous: $raw['previous'] ?? null,
                unit: $raw['unit'] ?? null,
                sourceUrl: null,
            ));
        }

        return $events->sortBy(fn (NormalizedCalendarEvent $e) => $e->scheduledAt->timestamp)->values();
    }

    /**
     * Statically defined events spanning the next 14 days from deploy time.
     * Update this list whenever the real calendar feed is unavailable.
     */
    private function staticEvents(): array
    {
        $base = now()->startOfDay();

        return [
            [
                'event_name' => 'US Consumer Price Index (CPI) YoY',
                'country'    => 'US',
                'currency'   => 'USD',
                'importance' => 'high',
                'date'       => $base->addDays(1)->setTime(12, 30)->toDateTimeString(),
                'actual'     => null,
                'forecast'   => '3.1%',
                'previous'   => '3.2%',
                'unit'       => '%',
            ],
            [
                'event_name' => 'US Non-Farm Payrolls',
                'country'    => 'US',
                'currency'   => 'USD',
                'importance' => 'high',
                'date'       => $base->addDays(3)->setTime(12, 30)->toDateTimeString(),
                'actual'     => null,
                'forecast'   => '180K',
                'previous'   => '175K',
                'unit'       => 'K',
            ],
            [
                'event_name' => 'ECB Interest Rate Decision',
                'country'    => 'EU',
                'currency'   => 'EUR',
                'importance' => 'high',
                'date'       => $base->addDays(5)->setTime(11, 15)->toDateTimeString(),
                'actual'     => null,
                'forecast'   => '4.00%',
                'previous'   => '4.25%',
                'unit'       => '%',
            ],
            [
                'event_name' => 'UK GDP MoM',
                'country'    => 'GB',
                'currency'   => 'GBP',
                'importance' => 'medium',
                'date'       => $base->addDays(7)->setTime(6, 0)->toDateTimeString(),
                'actual'     => null,
                'forecast'   => '0.1%',
                'previous'   => '-0.1%',
                'unit'       => '%',
            ],
            [
                'event_name' => 'US FOMC Interest Rate Decision',
                'country'    => 'US',
                'currency'   => 'USD',
                'importance' => 'high',
                'date'       => $base->addDays(10)->setTime(18, 0)->toDateTimeString(),
                'actual'     => null,
                'forecast'   => '5.25%',
                'previous'   => '5.50%',
                'unit'       => '%',
            ],
        ];
    }
}
