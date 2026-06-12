<?php

namespace App\Contracts;

use App\DTOs\NormalizedNewsItem;
use Carbon\CarbonInterface;

interface NewsProvider
{
    /**
     * Fetch news items published since the given timestamp.
     *
     * @return iterable<NormalizedNewsItem>
     */
    public function fetch(CarbonInterface $since): iterable;
}
