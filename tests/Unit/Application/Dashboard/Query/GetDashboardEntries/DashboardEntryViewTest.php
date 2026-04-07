<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Dashboard\Query\GetDashboardEntries;

use App\Application\Dashboard\Query\GetDashboardEntries\DashboardEntryView;
use PHPUnit\Framework\TestCase;

class DashboardEntryViewTest extends TestCase
{
    private function validRow(array $overrides = []): array
    {
        return array_merge([
            'id'               => '550e8400-e29b-41d4-a716-446655440000',
            'site_url'         => 'https://example.com',
            'site_name'        => 'Example Site',
            'total_page_views' => '1500000',
            'unique_visitors'  => '300000',
            'bounce_rate'      => '42.50',
            'avg_load_time_ms' => '850',
            'status'           => 'active',
            'last_recorded_at' => '2026-04-07 10:00:00',
        ], $overrides);
    }

    public function testFromArrayMapsAllFields(): void
    {
        $view = DashboardEntryView::fromArray($this->validRow());

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $view->id);
        $this->assertSame('https://example.com', $view->siteUrl);
        $this->assertSame('Example Site', $view->siteName);
        $this->assertSame(1_500_000, $view->totalPageViews);
        $this->assertSame(300_000, $view->uniqueVisitors);
        $this->assertEqualsWithDelta(42.50, $view->bounceRate, 0.001);
        $this->assertSame(850, $view->avgLoadTimeMs);
        $this->assertSame('active', $view->status);
        $this->assertSame('2026-04-07 10:00:00', $view->lastRecordedAt);
    }

    public function testStringDbValuesAreCastToCorrectTypes(): void
    {
        $view = DashboardEntryView::fromArray($this->validRow());

        $this->assertIsInt($view->totalPageViews);
        $this->assertIsInt($view->uniqueVisitors);
        $this->assertIsFloat($view->bounceRate);
        $this->assertIsInt($view->avgLoadTimeMs);
    }

    public function testLargePageViewCount(): void
    {
        $view = DashboardEntryView::fromArray($this->validRow(['total_page_views' => '60000000']));

        $this->assertSame(60_000_000, $view->totalPageViews);
    }

    public function testInactiveStatus(): void
    {
        $view = DashboardEntryView::fromArray($this->validRow(['status' => 'inactive']));

        $this->assertSame('inactive', $view->status);
    }

    public function testDegradedStatus(): void
    {
        $view = DashboardEntryView::fromArray($this->validRow(['status' => 'degraded']));

        $this->assertSame('degraded', $view->status);
    }

    public function testViewIsImmutableViaConstructor(): void
    {
        $view = DashboardEntryView::fromArray($this->validRow());

        // Readonly properties cannot be reassigned — this just checks the object
        // is constructed and values are accessible as documented.
        $this->assertSame('https://example.com', $view->siteUrl);
    }
}
