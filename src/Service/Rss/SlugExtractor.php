<?php

declare(strict_types=1);

namespace App\Service\Rss;

final class SlugExtractor
{
    public static function extract(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        $file = basename($path, '.html');
        $slug = preg_replace('/-\d+$/', '', $file);

        if (!$slug || ctype_digit($slug)) {
            return null;
        }

        return $slug;
    }
}
