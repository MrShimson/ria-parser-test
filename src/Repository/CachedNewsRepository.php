<?php

declare(strict_types=1);

namespace App\Repository;

use App\Cache\CacheVersion;
use App\Dto\NewsFilters;
use App\Dto\NewsPage;
use Memcached;

final class CachedNewsRepository implements NewsRepositoryInterface
{
    public function __construct(
        private readonly NewsRepository $inner,
        private readonly Memcached      $mc,
        private readonly CacheVersion   $version,
        private readonly int            $ttl = 3600,
    ) {}

    public function findByFilters(NewsFilters $f): NewsPage
    {
        $key = $this->newsKey($f);
        $hit = $this->mc->get($key);
        if ($hit !== false) {
            return $hit;
        }

        $page = $this->inner->findByFilters($f);
        $this->mc->set($key, $page, $this->ttl);
        return $page;
    }

    /** @return array<int, string> */
    public function findActiveCategories(): array
    {
        $key = $this->categoriesKey();
        $hit = $this->mc->get($key);
        if ($hit !== false) {
            return $hit;
        }

        $cats = $this->inner->findActiveCategories();
        $this->mc->set($key, $cats, $this->ttl);
        return $cats;
    }

    private function newsKey(NewsFilters $f): string
    {
        $v   = $this->version->getNewsVersion();
        $cat = $f->categoryId ?? 'all';
        return sprintf(
            'news:v%d:cat=%s:from=%s:to=%s:p=%d',
            $v,
            $cat,
            $f->fromUtc->format('Y-m-d\TH'),
            $f->toUtc->format('Y-m-d\TH'),
            $f->page,
        );
    }

    private function categoriesKey(): string
    {
        $v = $this->version->getCategoriesVersion();
        return "categories:v{$v}:active";
    }
}
