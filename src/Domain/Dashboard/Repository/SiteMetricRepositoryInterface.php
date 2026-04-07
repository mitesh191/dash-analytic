<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Repository;

use App\Entity\SiteMetric;

interface SiteMetricRepositoryInterface
{
    public function save(SiteMetric $metric): void;

    public function findById(string $id): ?SiteMetric;
}
