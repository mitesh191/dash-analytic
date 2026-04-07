<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Event;

/**
 * Raised after a new SiteMetric record is persisted.
 * Consumed async by SiteMetricProjector to update the read model.
 */
final class SiteMetricRecorded
{
    public function __construct(
        public readonly string             $siteMetricId,
        public readonly string             $siteUrl,
        public readonly string             $siteName,
        public readonly int                $pageViews,
        public readonly int                $uniqueVisitors,
        public readonly float              $bounceRate,
        public readonly int                $loadTimeMs,
        public readonly \DateTimeImmutable $recordedAt,
    ) {}
}
