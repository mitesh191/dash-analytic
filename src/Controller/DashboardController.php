<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Dashboard\Query\GetDashboardEntries\GetDashboardEntriesQuery;
use App\Infrastructure\Monitoring\DashboardRequestLogHandler;
use App\Infrastructure\Monitoring\MonitoringChecks;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard', name: 'dashboard_')]
final class DashboardController extends AbstractController
{
    public function __construct(
        #[Target('query.bus')]
        private readonly MessageBusInterface        $queryBus,
        private readonly DashboardRequestLogHandler $logHandler,
        private readonly MonitoringChecks           $monitoringChecks,
        private readonly LoggerInterface            $dashboardLogger,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page   = max(1, $request->query->getInt('page', 1));
        $limit  = min(200, max(10, $request->query->getInt('limit', 50)));

        $allowedStatuses = ['', 'active', 'inactive', 'degraded'];
        $rawStatus       = $request->query->getString('status', '');
        $status          = in_array($rawStatus, $allowedStatuses, true) ? $rawStatus : '';

        $allowedSortCols = ['site_name', 'total_page_views', 'unique_visitors', 'bounce_rate', 'avg_load_time_ms', 'last_recorded_at'];
        $rawSort         = $request->query->getString('sort', 'total_page_views');
        $sortBy          = in_array($rawSort, $allowedSortCols, true) ? $rawSort : 'total_page_views';

        $sortDir = strtoupper($request->query->getString('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $this->dashboardLogger->info('Dashboard request received', [
            'page' => $page, 'limit' => $limit, 'status' => $status ?: 'all',
            'sort' => "{$sortBy} {$sortDir}",
        ]);

        $envelope = $this->queryBus->dispatch(new GetDashboardEntriesQuery(
            page:    $page,
            limit:   $limit,
            status:  $status,
            sortBy:  $sortBy,
            sortDir: $sortDir,
        ));

        /** @var array{items: mixed[], total: int, page: int, limit: int, pages: int, _meta: array} $result */
        $result = $envelope->last(HandledStamp::class)->getResult();
        $meta   = $result['_meta'] ?? ['source' => 'unknown', 'cache_hit' => false, 'query_time_ms' => 0, 'is_slow' => false];

        $this->dashboardLogger->info('Dashboard query completed', [
            'total_records' => $result['total'],
            'source'        => $meta['source'],
            'query_time_ms' => $meta['query_time_ms'],
        ]);

        if ($meta['is_slow']) {
            $this->dashboardLogger->warning('Slow dashboard query detected', [
                'query_time_ms' => $meta['query_time_ms'],
                'threshold_ms'  => 500,
                'page'          => $page,
                'sort'          => "{$sortBy} {$sortDir}",
            ]);
        }

        $monitorResults = $this->monitoringChecks->run($result['items'], $meta);
        $logRecords     = $this->logHandler->getRecords();

        return $this->render('dashboard/index.html.twig', [
            'entries'        => $result['items'],
            'total'          => $result['total'],
            'page'           => $result['page'],
            'limit'          => $result['limit'],
            'pages'          => $result['pages'],
            'status'         => $status,
            'sortBy'         => $sortBy,
            'sortDir'        => $sortDir,
            'meta'           => $meta,
            'monitorResults' => $monitorResults,
            'logRecords'     => $logRecords,
        ]);
    }
}

