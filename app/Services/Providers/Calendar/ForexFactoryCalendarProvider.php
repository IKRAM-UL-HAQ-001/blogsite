<?php

namespace App\Services\Providers\Calendar;

use App\Contracts\EconomicCalendarProvider;
use App\DTOs\NormalizedCalendarEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches economic calendar data from the community-maintained ForexFactory
 * JSON mirror at nfs.faireconomy.media.
 *
 * This is a fragile adapter — the upstream data source can change or go offline.
 * The pipeline should always have a fallback (ManualCalendarProvider).
 */
final class ForexFactoryCalendarProvider implements EconomicCalendarProvider
{
    private const FEEDS = [
        'https://nfs.faireconomy.media/ff_calendar_thisweek.json',
        'https://nfs.faireconomy.media/ff_calendar_nextweek.json',
    ];

    // Impact values used by ForexFactory → normalized importance
    private const IMPACT_MAP = [
        'High'   => 'high',
        'Medium' => 'medium',
        'Low'    => 'low',
        'Non-Economic' => 'low',
        'Holiday' => 'low',
    ];

    public function fetch(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $events = collect();

        foreach (self::FEEDS as $url) {
            try {
                $response = Http::timeout(30)->get($url);

                if (! $response->successful()) {
                    Log::warning("ForexFactoryCalendarProvider: HTTP {$response->status()} from {$url}");
                    continue;
                }

                $data = $response->json();

                if (! is_array($data)) {
                    Log::warning("ForexFactoryCalendarProvider: unexpected response format from {$url}");
                    continue;
                }

                foreach ($data as $item) {
                    $dateStr = $item['date'] ?? null;

                    if (! $dateStr) {
                        continue;
                    }

                    try {
                        $scheduledAt = CarbonImmutable::parse($dateStr, 'America/New_York')->utc();
                    } catch (\Throwable) {
                        continue;
                    }

                    if ($scheduledAt->isBefore($from) || $scheduledAt->isAfter($to)) {
                        continue;
                    }

                    $name       = trim($item['title'] ?? '');
                    $country    = strtoupper(trim($item['country'] ?? ''));
                    $currency   = strtoupper(trim($item['currency'] ?? $country));
                    $rawImpact  = $item['impact'] ?? 'Low';
                    $importance = self::IMPACT_MAP[$rawImpact] ?? 'low';

                    if ($name === '' || $currency === '') {
                        continue;
                    }

                    $events->push(new NormalizedCalendarEvent(
                        eventName: $name,
                        country: $country,
                        currency: $currency,
                        importance: $importance,
                        scheduledAt: $scheduledAt,
                        actual: $item['actual'] ?? null ?: null,
                        forecast: $item['forecast'] ?? null ?: null,
                        previous: $item['previous'] ?? null ?: null,
                        unit: null,
                        sourceUrl: 'https://www.forexfactory.com/calendar',
                        metadata: ['raw_impact' => $rawImpact],
                    ));
                }
            } catch (\Throwable $e) {
                Log::error("ForexFactoryCalendarProvider: error processing {$url} — {$e->getMessage()}");
            }
        }

        return $events->sortBy(fn (NormalizedCalendarEvent $e) => $e->scheduledAt->timestamp)->values();
    }
}
