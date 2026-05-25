<?php

declare(strict_types=1);

namespace App\Cache;

use Memcached;

final class CacheVersion
{
    private const string NEWS_KEY       = 'news:version';
    private const string CATEGORIES_KEY = 'categories:version';

    public function __construct(private readonly Memcached $mc) {}

    public function getNewsVersion(): int
    {
        return $this->getOrInit(self::NEWS_KEY);
    }

    public function getCategoriesVersion(): int
    {
        return $this->getOrInit(self::CATEGORIES_KEY);
    }

    public function invalidate(): void
    {
        foreach ([self::NEWS_KEY, self::CATEGORIES_KEY] as $key) {
            // increment возвращает false на несуществующем ключе — инициализируем
            if ($this->mc->increment($key) === false) {
                $this->mc->add($key, 1);
            }
        }
    }

    private function getOrInit(string $key): int
    {
        $v = $this->mc->get($key);
        if ($v === false) {
            $this->mc->add($key, 1);
            $v = 1;
        }
        return (int) $v;
    }
}
