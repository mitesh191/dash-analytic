<?php

declare(strict_types=1);

namespace App\Application\Dashboard\Query\GetDashboardEntries;

final class GetDashboardEntriesQuery
{
    public function __construct(
        public readonly int    $page    = 1,
        public readonly int    $limit   = 50,
        public readonly string $status  = '',
        public readonly string $sortBy  = 'total_page_views',
        public readonly string $sortDir = 'DESC',
    ) {}
}
