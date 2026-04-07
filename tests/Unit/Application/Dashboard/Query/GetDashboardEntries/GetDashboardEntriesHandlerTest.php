<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Dashboard\Query\GetDashboardEntries;

use App\Application\Dashboard\Query\GetDashboardEntries\DashboardEntryView;
use App\Application\Dashboard\Query\GetDashboardEntries\GetDashboardEntriesHandler;
use App\Application\Dashboard\Query\GetDashboardEntries\GetDashboardEntriesQuery;
use App\Infrastructure\Dashboard\ReadModel\DashboardReadModelRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetDashboardEntriesHandlerTest extends TestCase
{
    private MockObject&DashboardReadModelRepositoryInterface $readModel;
    private GetDashboardEntriesHandler $handler;

    protected function setUp(): void
    {
        $this->readModel = $this->createMock(DashboardReadModelRepositoryInterface::class);
        $this->handler   = new GetDashboardEntriesHandler($this->readModel);
    }

    private function emptyResult(int $page = 1, int $limit = 50): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'limit' => $limit, 'pages' => 0];
    }

    public function testHandlerDelegatesToReadModel(): void
    {
        $expected = $this->emptyResult();

        $this->readModel
            ->expects($this->once())
            ->method('findPaginated')
            ->with(1, 50, null, 'total_page_views', 'DESC')
            ->willReturn($expected);

        $result = ($this->handler)(new GetDashboardEntriesQuery(1, 50, '', 'total_page_views', 'DESC'));

        $this->assertSame($expected, $result);
    }

    public function testEmptyStatusStringIsConvertedToNull(): void
    {
        $this->readModel
            ->expects($this->once())
            ->method('findPaginated')
            ->with(1, 50, null, 'total_page_views', 'DESC')
            ->willReturn($this->emptyResult());

        ($this->handler)(new GetDashboardEntriesQuery(1, 50, '', 'total_page_views', 'DESC'));
    }

    public function testNonEmptyStatusIsPassedThrough(): void
    {
        $this->readModel
            ->expects($this->once())
            ->method('findPaginated')
            ->with(1, 50, 'active', 'total_page_views', 'DESC')
            ->willReturn($this->emptyResult());

        ($this->handler)(new GetDashboardEntriesQuery(1, 50, 'active', 'total_page_views', 'DESC'));
    }

    public function testPaginationParametersAreForwarded(): void
    {
        $this->readModel
            ->expects($this->once())
            ->method('findPaginated')
            ->with(5, 100, null, 'site_name', 'ASC')
            ->willReturn($this->emptyResult(5, 100));

        $result = ($this->handler)(new GetDashboardEntriesQuery(5, 100, '', 'site_name', 'ASC'));

        $this->assertSame(5, $result['page']);
        $this->assertSame(100, $result['limit']);
    }

    public function testHandlerReturnsItemsFromReadModel(): void
    {
        $entry = new DashboardEntryView(
            id:             '550e8400-e29b-41d4-a716-446655440000',
            siteUrl:        'https://example.com',
            siteName:       'Example',
            totalPageViews: 1_000,
            uniqueVisitors: 500,
            bounceRate:     42.5,
            avgLoadTimeMs:  300,
            status:         'active',
            lastRecordedAt: '2026-04-07 10:00:00',
        );

        $this->readModel
            ->expects($this->once())
            ->method('findPaginated')
            ->willReturn(['items' => [$entry], 'total' => 1, 'page' => 1, 'limit' => 50, 'pages' => 1]);

        $result = ($this->handler)(new GetDashboardEntriesQuery());

        $this->assertCount(1, $result['items']);
        $this->assertSame(1, $result['total']);
        $this->assertSame($entry, $result['items'][0]);
    }
}
