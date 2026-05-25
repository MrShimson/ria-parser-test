<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class ListNewsResult
{
    /** @param array<int, string> $categories id => slug */
    public function __construct(
        public NewsPage    $page,
        public array       $categories,
        public NewsFilters $appliedFilters,
    ) {}
}
