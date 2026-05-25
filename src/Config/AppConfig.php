<?php

declare(strict_types=1);

namespace App\Config;

final readonly class AppConfig
{
    public function __construct(
        public string $dbDsn,
        public string $dbUser,
        public string $dbPass,
        public string $mcHost,
        public int    $mcPort,
        public string $rssUrl,
        public int    $pageSize,
        public int    $defaultWindowDays,
        public int    $maxWindowDays,
        public int    $cacheTtl,
        public string $appTimezone,
    ) {}

    public static function fromEnv(): self
    {
        $host = self::env('DB_HOST', 'db');
        $name = self::env('DB_NAME', 'news');

        return new self(
            dbDsn:             "mysql:host={$host};dbname={$name};charset=utf8mb4",
            dbUser:            self::env('DB_USER', 'news'),
            dbPass:            self::env('DB_PASS', 'newspass'),
            mcHost:            self::env('MC_HOST', 'memcached'),
            mcPort:            (int) self::env('MC_PORT', '11211'),
            rssUrl:            self::env('RSS_URL', 'https://ria.ru/export/rss2/archive/index.xml'),
            pageSize:          20,
            defaultWindowDays: 7,
            maxWindowDays:     30,
            cacheTtl:          3600,
            appTimezone:       'Europe/Moscow',
        );
    }

    private static function env(string $key, string $default = ''): string
    {
        $v = getenv($key);
        return $v !== false ? $v : $default;
    }
}
