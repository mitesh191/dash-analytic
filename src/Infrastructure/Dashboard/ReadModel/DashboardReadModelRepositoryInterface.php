<?php

declare(strict_types=1);

namespace App\Infrastructure\Dashboard\ReadModel;

use App\Application\Dashboard\Query\GetDashboardEntries\DashboardEntryView;

interface DashboardReadModelRepositoryInterface
{
    /**
     * @return array{items: DashboardEntryView[], total: int, page: int, limit: int, pages: int}
     */
    public function findPaginated(
        int     $page    = 1,
        int     $limit   = 50,
        ?string $status  = null,
        string  $sortBy  = 'total_page_views',
        string  $sortDir = 'DESC',
    ): array;
}
