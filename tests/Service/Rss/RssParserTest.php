<?php

declare(strict_types=1);

namespace App\Tests\Service\Rss;

use App\Service\Rss\RssParser;
use PHPUnit\Framework\TestCase;

final class RssParserTest extends TestCase
{
    private RssParser $parser;

    protected function setUp(): void
    {
        $this->parser = new RssParser();
    }

    private function fixture(): string
    {
        return file_get_contents(__DIR__ . '/../../fixtures/ria-sample.xml');
    }

    public function testParsesAllItems(): void
    {
        $items = iterator_to_array($this->parser->parse($this->fixture()));
        self::assertCount(3, $items);
    }

    public function testSlugExtracted(): void
    {
        $items = iterator_to_array($this->parser->parse($this->fixture()));
        self::assertSame('medvedev', $items[0]->categorySlug);
        self::assertSame('dollar',   $items[1]->categorySlug);
        self::assertSame('sport',    $items[2]->categorySlug);
    }

    public function testDateNormalizedToUtc(): void
    {
        $items = iterator_to_array($this->parser->parse($this->fixture()));

        // +0300 → UTC: 13:25 - 3h = 10:25
        self::assertSame('UTC', $items[0]->publishedAtUtc->getTimezone()->getName());
        self::assertSame('2026-05-22 10:25:21', $items[0]->publishedAtUtc->format('Y-m-d H:i:s'));
    }

    public function testMissingEnclosureIsNull(): void
    {
        $items = iterator_to_array($this->parser->parse($this->fixture()));
        self::assertNull($items[1]->imageUrl);
    }

    public function testMissingDescriptionIsNull(): void
    {
        $items = iterator_to_array($this->parser->parse($this->fixture()));
        self::assertNull($items[1]->description);
    }

    public function testCdataDescription(): void
    {
        $items = iterator_to_array($this->parser->parse($this->fixture()));
        self::assertSame('CDATA описание новости.', $items[2]->description);
    }

    public function testUtcPubDateUnchanged(): void
    {
        $items = iterator_to_array($this->parser->parse($this->fixture()));
        self::assertSame('2026-05-22 10:30:00', $items[2]->publishedAtUtc->format('Y-m-d H:i:s'));
    }
}
