<?php

declare(strict_types=1);

namespace App\Service\Rss;

final class CurlRssClient implements RssClientInterface
{
    public function __construct(
        private readonly int    $timeout   = 10,
        private readonly string $userAgent = 'NewsAggregator/1.0',
    ) {}

    public function fetch(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!$body) {
            throw new RssFetchException("cURL error for {$url}: {$error}");
        }

        if ($code !== 200) {
            throw new RssFetchException("HTTP {$code} fetching {$url}");
        }

        return $body;
    }
}
