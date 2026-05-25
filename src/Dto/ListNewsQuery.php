<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class ListNewsQuery
{
    public function __construct(
        public ?int    $categoryId,
        public ?string $fromDate,
        public ?string $toDate,
        public int     $page,
    ) {}

    public static function fromRequest(array $get): self
    {
        return new self(
            categoryId: self::intOrNull($get['category'] ?? null),
            fromDate:   self::stringOrNull($get['from']  ?? null),
            toDate:     self::stringOrNull($get['to']    ?? null),
            page:       self::intOrNull($get['page'] ?? null) ?? 1,
        );
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
