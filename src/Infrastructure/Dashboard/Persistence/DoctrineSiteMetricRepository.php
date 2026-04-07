<?php

declare(strict_types=1);

namespace App\Infrastructure\Dashboard\Persistence;

use App\Domain\Dashboard\Repository\SiteMetricRepositoryInterface;
use App\Entity\SiteMetric;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSiteMetricRepository implements SiteMetricRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function save(SiteMetric $metric): void
    {
        $this->em->persist($metric);
        $this->em->flush();
    }

    public function findById(string $id): ?SiteMetric
    {
        return $this->em->find(SiteMetric::class, $id);
    }
}
