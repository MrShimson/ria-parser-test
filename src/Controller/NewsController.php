<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\ListNewsUseCase;
use App\Cache\CacheVersion;
use App\Dto\ListNewsQuery;

final class NewsController
{
    public function __construct(
        private readonly ListNewsUseCase $useCase,
        private readonly CacheVersion    $cacheVersion,
    ) {}

    public function index(array $get): void
    {
        if ($this->handleEtagHeader()) {
            return;
        }

        $result = $this->useCase->handle(ListNewsQuery::fromRequest($get));

        $newsPage   = $result->page;
        $categories = $result->categories;
        $filters    = $result->appliedFilters;

        require __DIR__ . '/../../templates/index.php';
    }

    /** @return bool true если был отправлен 304 и обработку нужно прервать */
    private function handleEtagHeader(): bool
    {
        $etag = sprintf('"v%d"', $this->cacheVersion->getNewsVersion());
        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;

        // no-cache заставляет браузер всегда перепроверять ETag условным запросом
        header('Cache-Control: no-cache');
        header('ETag: ' . $etag);

        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            header('HTTP/1.1 304 Not Modified');
            return true;
        }

        return false;
    }
}
