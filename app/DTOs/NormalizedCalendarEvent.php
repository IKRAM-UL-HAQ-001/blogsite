<?php

namespace App\DTOs;

use Carbon\CarbonImmutable;

final readonly class NormalizedCalendarEvent
{
    public function __construct(
        public string $eventName,
        public string $country,
        public string $currency,
        public string $importance,
        public CarbonImmutable $scheduledAt,
        public ?string $actual,
        public ?string $forecast,
        public ?string $previous,
        public ?string $unit,
        public ?string $sourceUrl,
        public array $metadata = [],
    ) {}
}
