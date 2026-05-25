<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\NewsFilters;
use App\Dto\NewsPage;

interface NewsRepositoryInterface
{
    public function findByFilters(NewsFilters $filters): NewsPage;

    /** @return array<int, string> id => slug */
    public function findActiveCategories(): array;
}
