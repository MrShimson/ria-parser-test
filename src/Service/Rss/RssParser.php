<?php

declare(strict_types=1);

namespace App\Service\Rss;

use App\Dto\NewsItem;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

final class RssParser
{
    private const UTC = 'UTC';

    public function __construct(private readonly ?LoggerInterface $logger = null) {}

    /** @return iterable<NewsItem> */
    public function parse(string $xml): iterable
    {
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string(data: $xml, options: LIBXML_NOCDATA);

        if ($feed === false) {
            $errors = array_map(static fn($e) => $e->message, libxml_get_errors());
            libxml_clear_errors();
            throw new RssFetchException('XML parse error: ' . implode('; ', $errors));
        }

        // try/catch внутри генератора: иначе исключение закроет генератор
        // в потребителе и оставшиеся item'ы будут потеряны
        foreach ($feed->channel->item as $item) {
            try {
                yield $this->parseItem($item);
            } catch (RssItemException $e) {
                $this->logger?->warning('Skipping bad RSS item', ['err' => $e->getMessage()]);
            }
        }
    }

    private function parseItem(SimpleXMLElement $item): NewsItem
    {
        $url   = trim((string) $item->link);
        $title = trim((string) $item->title);

        if ($url === '' || $title === '') {
            throw new RssItemException('Item missing url or title');
        }

        $slug = SlugExtractor::extract($url);
        if ($slug === null) {
            throw new RssItemException("Cannot extract slug from url: {$url}");
        }

        $description = trim((string) $item->description);
        $description = $description !== '' ? $description : null;

        $imageUrl = trim((string) ($item->enclosure['url'] ?? ''));
        $imageUrl = $imageUrl !== '' ? $imageUrl : null;

        $pubDate = trim((string) $item->pubDate);
        try {
            $publishedAt = (new DateTimeImmutable($pubDate))
                ->setTimezone(new DateTimeZone(self::UTC));
        } catch (\Throwable $e) {
            throw new RssItemException("Invalid pubDate '{$pubDate}': " . $e->getMessage());
        }

        return new NewsItem(
            url:            $url,
            title:          $title,
            description:    $description,
            categorySlug:   $slug,
            imageUrl:       $imageUrl,
            publishedAtUtc: $publishedAt,
        );
    }
}
