<?php

declare(strict_types=1);

namespace App\Dto;

use DateTimeImmutable;

final readonly class NewsRow
{
    public function __construct(
        public int               $id,
        public string            $url,
        public string            $title,
        public ?string           $description,
        public string            $slug,
        public ?string           $imageUrl,
        public DateTimeImmutable $publishedAtUtc,
    ) {}
}
