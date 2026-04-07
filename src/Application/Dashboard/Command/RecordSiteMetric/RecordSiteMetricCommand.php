<?php

declare(strict_types=1);

namespace App\Application\Dashboard\Command\RecordSiteMetric;

final class RecordSiteMetricCommand
{
    public function __construct(
        public readonly string $siteUrl,
        public readonly string $siteName,
        public readonly int    $pageViews,
        public readonly int    $uniqueVisitors,
        public readonly float  $bounceRate,
        public readonly int    $loadTimeMs,
    ) {}
}
