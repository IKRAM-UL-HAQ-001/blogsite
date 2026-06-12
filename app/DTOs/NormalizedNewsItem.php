<?php

namespace App\DTOs;

use Carbon\CarbonImmutable;

final readonly class NormalizedNewsItem
{
    public function __construct(
        public string $source,
        public ?string $externalId,
        public string $url,
        public string $title,
        public ?string $summary,
        public ?string $body,
        public ?string $author,
        public string $language,
        public CarbonImmutable $publishedAt,
        public array $metadata = [],
    ) {}

    public function contentHash(): string
    {
        return hash('sha256', mb_strtolower(trim($this->title) . '|' . trim($this->body ?? '')));
    }
}
