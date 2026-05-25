<?php

declare(strict_types=1);

namespace App;

use App\Config\AppConfig;
use App\Logger\StderrLogger;
use Memcached;
use PDO;
use Psr\Log\LoggerInterface;

final class Bootstrap
{
    /** @return array{PDO, Memcached, LoggerInterface} */
    public static function make(AppConfig $config): array
    {
        $pdo = new PDO($config->dbDsn, $config->dbUser, $config->dbPass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ]);

        $mc = new Memcached();
        $mc->addServer($config->mcHost, $config->mcPort);

        $logger = new StderrLogger();

        return [$pdo, $mc, $logger];
    }
}
