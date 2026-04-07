<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\DashboardReadEntry;
use PHPUnit\Framework\TestCase;

class DashboardReadEntryTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    private function makeEntry(
        int    $totalPageViews = 1_000,
        int    $uniqueVisitors = 500,
        float  $bounceRate     = 45.00,
        int    $avgLoadTimeMs  = 600,
        string $status         = 'active',
    ): DashboardReadEntry {
        return new DashboardReadEntry(
            id:             self::UUID,
            siteUrl:        'https://example.com',
            siteName:       'Example Site',
            totalPageViews: $totalPageViews,
            uniqueVisitors: $uniqueVisitors,
            bounceRate:     $bounceRate,
            avgLoadTimeMs:  $avgLoadTimeMs,
            status:         $status,
            lastRecordedAt: new \DateTimeImmutable('2026-01-01 12:00:00'),
        );
    }

    public function testConstructorSetsAllFieldsCorrectly(): void
    {
        $entry = $this->makeEntry();

        $this->assertSame(self::UUID, $entry->getId());
        $this->assertSame('https://example.com', $entry->getSiteUrl());
        $this->assertSame('Example Site', $entry->getSiteName());
        $this->assertSame(1_000, $entry->getTotalPageViews());
        $this->assertSame(500, $entry->getUniqueVisitors());
        $this->assertEqualsWithDelta(45.00, $entry->getBounceRate(), 0.001);
        $this->assertSame(600, $entry->getAvgLoadTimeMs());
        $this->assertSame('active', $entry->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entry->getLastRecordedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entry->getUpdatedAt());
    }

    public function testUpdateAccumulatesTotalPageViews(): void
    {
        $entry = $this->makeEntry(totalPageViews: 1_000);

        $entry->updateFromMetric(
            additionalPageViews: 500,
            uniqueVisitors:      300,
            newBounceRate:       40.0,
            newLoadTimeMs:       400,
            status:              'active',
            recordedAt:          new \DateTimeImmutable(),
        );

        $this->assertSame(1_500, $entry->getTotalPageViews());
    }

    public function testUpdateAveragesLoadTime(): void
    {
        $entry = $this->makeEntry(avgLoadTimeMs: 600);

        $entry->updateFromMetric(
            additionalPageViews: 0,
            uniqueVisitors:      300,
            newBounceRate:       40.0,
            newLoadTimeMs:       200, // (600 + 200) / 2 = 400
            status:              'active',
            recordedAt:          new \DateTimeImmutable(),
        );

        $this->assertSame(400, $entry->getAvgLoadTimeMs());
    }

    public function testUpdateChangesStatus(): void
    {
        $entry = $this->makeEntry(status: 'active');

        $entry->updateFromMetric(
            additionalPageViews: 10,
            uniqueVisitors:      5,
            newBounceRate:       55.0,
            newLoadTimeMs:       5_000,
            status:              'degraded',
            recordedAt:          new \DateTimeImmutable(),
        );

        $this->assertSame('degraded', $entry->getStatus());
    }

    public function testUpdateRefreshesUpdatedAt(): void
    {
        $entry  = $this->makeEntry();
        $before = $entry->getUpdatedAt();

        // Small sleep to ensure timestamp differs
        usleep(1_000);

        $entry->updateFromMetric(10, 5, 50.0, 600, 'active', new \DateTimeImmutable());

        $this->assertGreaterThanOrEqual($before, $entry->getUpdatedAt());
    }

    public function testGetTotalPageViewsReturnsInt(): void
    {
        $this->assertIsInt($this->makeEntry()->getTotalPageViews());
    }

    public function testGetBounceRateReturnsFloat(): void
    {
        $this->assertIsFloat($this->makeEntry()->getBounceRate());
    }

    public function testUpdateWithZeroAdditionalViewsDoesNotDecrement(): void
    {
        $entry = $this->makeEntry(totalPageViews: 5_000);

        $entry->updateFromMetric(0, 100, 30.0, 300, 'active', new \DateTimeImmutable());

        $this->assertSame(5_000, $entry->getTotalPageViews());
    }
}
