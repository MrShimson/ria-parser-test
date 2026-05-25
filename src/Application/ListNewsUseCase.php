<?php

declare(strict_types=1);

namespace App\Application;

use App\Config\AppConfig;
use App\Dto\ListNewsQuery;
use App\Dto\ListNewsResult;
use App\Dto\NewsFilters;
use App\Repository\NewsRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;

final class ListNewsUseCase
{
    public function __construct(
        private readonly NewsRepositoryInterface $repo,
        private readonly AppConfig               $config,
    ) {}

    public function handle(ListNewsQuery $query): ListNewsResult
    {
        $appTz = new DateTimeZone($this->config->appTimezone);
        $utc   = new DateTimeZone('UTC');

        $nowApp = new DateTimeImmutable('now', $appTz);

        // to округляется вверх — иначе новости текущего часа/дня выпадают из выдачи
        $defaultTo   = $nowApp->setTime((int) $nowApp->format('H'), 59, 59);
        $defaultFrom = $defaultTo->modify("-{$this->config->defaultWindowDays} days");

        $fromPicked = $this->parseDate($query->fromDate, $appTz);
        $toPicked   = $this->parseDate($query->toDate,   $appTz);

        $from = $fromPicked ?? $defaultFrom;
        $to   = $toPicked?->setTime(23, 59, 59) ?? $defaultTo;

        $maxDays = $this->config->maxWindowDays;
        if ($to->diff($from)->days > $maxDays) {
            $from = $to->modify("-{$maxDays} days");
        }

        $filters = new NewsFilters(
            categoryId: $query->categoryId,
            fromUtc:    $from->setTimezone($utc),
            toUtc:      $to->setTimezone($utc),
            page:       max(1, $query->page),
            perPage:    $this->config->pageSize,
        );

        $page       = $this->repo->findByFilters($filters);
        $categories = $this->repo->findActiveCategories();

        return new ListNewsResult($page, $categories, $filters);
    }

    private function parseDate(?string $value, DateTimeZone $tz): ?DateTimeImmutable
    {
        if ($value === null || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return new DateTimeImmutable($value, $tz);
        } catch (\Throwable) {
            return null;
        }
    }
}
