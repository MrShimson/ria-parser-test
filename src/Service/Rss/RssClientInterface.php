<?php

declare(strict_types=1);

namespace App\Service\Rss;

interface RssClientInterface
{
    /** @throws RssFetchException */
    public function fetch(string $url): string;
}
