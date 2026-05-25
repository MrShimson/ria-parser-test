<?php

declare(strict_types=1);

namespace App\Dto;

use DateTimeImmutable;

final readonly class NewsItem
{
    public function __construct(
        public string            $url,
        public string            $title,
        public ?string           $description,
        public string            $categorySlug,
        public ?string           $imageUrl,
        public DateTimeImmutable $publishedAtUtc,
    ) {}
}
