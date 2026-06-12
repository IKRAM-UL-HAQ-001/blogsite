<?php

namespace App\Contracts;

use App\DTOs\NormalizedCalendarEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface EconomicCalendarProvider
{
    /**
     * Fetch calendar events scheduled between $from and $to.
     *
     * @return Collection<int, NormalizedCalendarEvent>
     */
    public function fetch(CarbonInterface $from, CarbonInterface $to): Collection;
}
