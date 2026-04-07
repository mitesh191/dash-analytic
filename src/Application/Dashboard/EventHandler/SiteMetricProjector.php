<?php

declare(strict_types=1);

namespace App\Application\Dashboard\EventHandler;

use App\Domain\Dashboard\Event\SiteMetricRecorded;
use App\Domain\Dashboard\ValueObject\MetricStatus;
use App\Entity\DashboardReadEntry;
use App\Shared\UuidGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Async projection handler.
 * Receives SiteMetricRecorded from the event transport and upserts
 * the denormalized DashboardReadEntry row for that site.
 */
#[AsMessageHandler(bus: 'event.bus')]
final class SiteMetricProjector
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CacheItemPoolInterface $cache,
    ) {}

    public function __invoke(SiteMetricRecorded $event): void
    {
        /** @var DashboardReadEntry|null $entry */
        $entry = $this->em
            ->getRepository(DashboardReadEntry::class)
            ->findOneBy(['siteUrl' => $event->siteUrl]);

        if ($entry === null) {
            $entry = new DashboardReadEntry(
                id:             UuidGenerator::generate(),
                siteUrl:        $event->siteUrl,
                siteName:       $event->siteName,
                totalPageViews: $event->pageViews,
                uniqueVisitors: $event->uniqueVisitors,
                bounceRate:     $event->bounceRate,
                avgLoadTimeMs:  $event->loadTimeMs,
                status:         MetricStatus::Active->value,
                lastRecordedAt: $event->recordedAt,
            );
            $this->em->persist($entry);
        } else {
            $entry->updateFromMetric(
                additionalPageViews: $event->pageViews,
                uniqueVisitors:      $event->uniqueVisitors,
                newBounceRate:       $event->bounceRate,
                newLoadTimeMs:       $event->loadTimeMs,
                status:              MetricStatus::Active->value,
                recordedAt:          $event->recordedAt,
            );
        }

        $this->em->flush();

        // Clear the dedicated dashboard cache pool so the updated read model
        // is visible on the next request rather than serving stale data.
        // clear() is safe here because the pool is exclusive to the dashboard.
        $this->cache->clear();
    }
}
