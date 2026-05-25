<?php

declare(strict_types=1);

namespace App\Application;

use App\Cache\CacheVersion;
use App\Repository\CategoryRepository;
use App\Repository\NewsRepository;
use App\Service\Rss\RssClientInterface;
use App\Service\Rss\RssFetchException;
use App\Service\Rss\RssParser;
use Psr\Log\LoggerInterface;
use Throwable;

final class ParseFeedHandler
{
    public function __construct(
        private readonly RssClientInterface  $client,
        private readonly RssParser           $parser,
        private readonly CategoryRepository  $categoryRepo,
        private readonly NewsRepository      $newsRepo,
        private readonly CacheVersion        $cacheVersion,
        private readonly LoggerInterface     $logger,
        private readonly string              $rssUrl,
    ) {}

    public function run(): void
    {
        try {
            $xml = $this->client->fetch($this->rssUrl);
        } catch (RssFetchException $e) {
            $this->logger->error('Fetch failed, aborting', ['err' => $e->getMessage()]);
            return;
        }

        try {
            $items = $this->parser->parse($xml);
        } catch (Throwable $e) {
            $this->logger->error('XML parse failed, aborting', ['err' => $e->getMessage()]);
            return;
        }

        $map     = $this->categoryRepo->loadMap();
        $saved   = 0;
        $skipped = 0;

        foreach ($items as $item) {
            try {
                $slug = $item->categorySlug;

                if (!isset($map[$slug])) {
                    $id       = $this->categoryRepo->upsert($slug);
                    $map[$slug] = $id;
                }

                $this->newsRepo->upsert($item, $map[$slug]);
                $saved++;
            } catch (Throwable $e) {
                $this->logger->warning('Skipping item', [
                    'url' => $item->url ?? '?',
                    'err' => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        $this->cacheVersion->invalidate();
        $this->logger->info('Feed parsed', ['saved' => $saved, 'skipped' => $skipped]);
    }
}
