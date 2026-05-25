#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\ParseFeedHandler;
use App\Bootstrap;
use App\Cache\CacheVersion;
use App\Config\AppConfig;
use App\Repository\CategoryRepository;
use App\Repository\NewsRepository;
use App\Service\Rss\CurlRssClient;
use App\Service\Rss\RssParser;

$config = AppConfig::fromEnv();

try {
    [$pdo, $mc, $logger] = Bootstrap::make($config);
} catch (Throwable $e) {
    fwrite(STDERR, "[FATAL] bootstrap failed: {$e->getMessage()}\n");
    exit(1);
}

$handler = new ParseFeedHandler(
    client:       new CurlRssClient(),
    parser:       new RssParser($logger),
    categoryRepo: new CategoryRepository($pdo),
    newsRepo:     new NewsRepository($pdo),
    cacheVersion: new CacheVersion($mc),
    logger:       $logger,
    rssUrl:       $config->rssUrl,
);

$handler->run();
exit(0);
