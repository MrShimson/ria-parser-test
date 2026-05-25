<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\ListNewsUseCase;
use App\Bootstrap;
use App\Cache\CacheVersion;
use App\Config\AppConfig;
use App\Controller\NewsController;
use App\Repository\CachedNewsRepository;
use App\Repository\NewsRepository;

$config = AppConfig::fromEnv();

[$pdo, $mc, $logger] = Bootstrap::make($config);

$cacheVersion = new CacheVersion($mc);
$repo         = new CachedNewsRepository(
    new NewsRepository($pdo),
    $mc,
    $cacheVersion,
    $config->cacheTtl,
);

$useCase    = new ListNewsUseCase($repo, $config);
$controller = new NewsController($useCase, $cacheVersion);
$controller->index($_GET);
