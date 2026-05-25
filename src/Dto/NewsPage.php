<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class NewsPage
{
    /** @param NewsRow[] $items */
    public function __construct(
        public array $items,
        public int   $total,
        public int   $page,
        public int   $perPage,
    ) {}

    public function totalPages(): int
    {
        return $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 1;
    }
}
