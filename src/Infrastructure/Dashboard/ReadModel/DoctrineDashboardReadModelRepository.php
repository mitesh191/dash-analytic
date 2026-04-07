<?php

declare(strict_types=1);

namespace App\Infrastructure\Dashboard\ReadModel;

use App\Application\Dashboard\Query\GetDashboardEntries\DashboardEntryView;
use Doctrine\DBAL\Connection;

/**
 * Uses DBAL directly — bypasses ORM hydration for maximum read throughput.
 * The allowlist guard on $sortBy prevents SQL injection on dynamic ORDER BY.
 */
final class DoctrineDashboardReadModelRepository implements DashboardReadModelRepositoryInterface
{
    private const ALLOWED_SORT_COLUMNS = [
        'site_name',
        'total_page_views',
        'unique_visitors',
        'bounce_rate',
        'avg_load_time_ms',
        'last_recorded_at',
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function findPaginated(
        int     $page    = 1,
        int     $limit   = 50,
        ?string $status  = null,
        string  $sortBy  = 'total_page_views',
        string  $sortDir = 'DESC',
    ): array {
        $sortBy  = in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true) ? $sortBy : 'total_page_views';
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';
        $offset  = ($page - 1) * $limit;

        $params = [];
        $where  = '';

        if ($status !== null) {
            $where            = 'WHERE status = :status';
            $params['status'] = $status;
        }

        $start = hrtime(true);

        $total = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM dashboard_read_entries {$where}",
            $params,
        );

        // LIMIT/OFFSET cannot be bound as named parameters in MySQL — inline them
        // directly. Both values are typed int so there is no injection risk.
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, site_url, site_name, total_page_views, unique_visitors,
                    bounce_rate, avg_load_time_ms, status, last_recorded_at
             FROM dashboard_read_entries
             {$where}
             ORDER BY {$sortBy} {$sortDir}
             LIMIT {$limit} OFFSET {$offset}",
            $params,
        );

        $queryTimeMs = (int) round((hrtime(true) - $start) / 1_000_000);

        return [
            'items'  => array_map(DashboardEntryView::fromArray(...), $rows),
            'total'  => $total,
            'page'   => $page,
            'limit'  => $limit,
            'pages'  => (int) ceil($total / max(1, $limit)),
            '_meta'  => [
                'source'        => 'database',
                'cache_hit'     => false,
                'query_time_ms' => $queryTimeMs,
                'is_slow'       => $queryTimeMs >= 500,
            ],
        ];
    }
}
