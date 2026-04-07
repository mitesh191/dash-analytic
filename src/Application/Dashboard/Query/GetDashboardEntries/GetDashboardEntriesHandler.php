<?php

declare(strict_types=1);

namespace App\Application\Dashboard\Query\GetDashboardEntries;

use App\Infrastructure\Dashboard\ReadModel\DashboardReadModelRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetDashboardEntriesHandler
{
    public function __construct(
        private readonly DashboardReadModelRepositoryInterface $readModel,
    ) {}

    /**
     * @return array{items: DashboardEntryView[], total: int, page: int, limit: int, pages: int}
     */
    public function __invoke(GetDashboardEntriesQuery $query): array
    {
        return $this->readModel->findPaginated(
            page:    $query->page,
            limit:   $query->limit,
            status:  $query->status !== '' ? $query->status : null,
            sortBy:  $query->sortBy,
            sortDir: $query->sortDir,
        );
    }
}
