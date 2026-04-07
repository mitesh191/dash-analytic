<?php

declare(strict_types=1);

namespace App\Application\Dashboard\Query\GetDashboardEntries;

/**
 * Immutable DTO returned from the read model.
 * Maps raw DB column names (snake_case) onto typed properties.
 */
final class DashboardEntryView
{
    public function __construct(
        public readonly string $id,
        public readonly string $siteUrl,
        public readonly string $siteName,
        public readonly int    $totalPageViews,
        public readonly int    $uniqueVisitors,
        public readonly float  $bounceRate,
        public readonly int    $avgLoadTimeMs,
        public readonly string $status,
        public readonly string $lastRecordedAt,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id:             $row['id'],
            siteUrl:        $row['site_url'],
            siteName:       $row['site_name'],
            totalPageViews: (int) $row['total_page_views'],
            uniqueVisitors: (int) $row['unique_visitors'],
            bounceRate:     (float) $row['bounce_rate'],
            avgLoadTimeMs:  (int) $row['avg_load_time_ms'],
            status:         $row['status'],
            lastRecordedAt: $row['last_recorded_at'],
        );
    }
}
