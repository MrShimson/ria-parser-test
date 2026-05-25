<?php

declare(strict_types=1);

namespace App\Tests\Service\Rss;

use App\Service\Rss\SlugExtractor;
use PHPUnit\Framework\TestCase;

final class SlugExtractorTest extends TestCase
{
    public function testNormalUrl(): void
    {
        self::assertSame(
            'medvedev',
            SlugExtractor::extract('https://ria.ru/20260522/medvedev-2094017408.html')
        );
    }

    public function testMultiWordSlug(): void
    {
        self::assertSame(
            'dollar-rose',
            SlugExtractor::extract('https://ria.ru/20260522/dollar-rose-2094017001.html')
        );
    }

    public function testOnlyDigitsSlug(): void
    {
        self::assertNull(
            SlugExtractor::extract('https://ria.ru/20260522/2094017001.html')
        );
    }

    public function testEmptyPath(): void
    {
        self::assertNull(SlugExtractor::extract('https://ria.ru/'));
    }

    public function testNoExtension(): void
    {
        self::assertSame(
            'sport',
            SlugExtractor::extract('https://ria.ru/20260522/sport-123')
        );
    }
}
