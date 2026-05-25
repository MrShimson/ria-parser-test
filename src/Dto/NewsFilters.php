<?php

declare(strict_types=1);

namespace App\Dto;

use DateTimeImmutable;

final readonly class NewsFilters
{
    public function __construct(
        public ?int              $categoryId,
        public DateTimeImmutable $fromUtc,
        public DateTimeImmutable $toUtc,
        public int               $page,
        public int               $perPage,
    ) {}
}
