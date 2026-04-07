<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DashboardReadEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DashboardReadEntry>
 */
class DashboardReadEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DashboardReadEntry::class);
    }
}
