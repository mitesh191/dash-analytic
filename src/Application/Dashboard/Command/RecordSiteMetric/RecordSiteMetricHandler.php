<?php

declare(strict_types=1);

namespace App\Application\Dashboard\Command\RecordSiteMetric;

use App\Domain\Dashboard\Event\SiteMetricRecorded;
use App\Domain\Dashboard\Repository\SiteMetricRepositoryInterface;
use App\Domain\Dashboard\ValueObject\SiteUrl;
use App\Entity\SiteMetric;
use App\Shared\UuidGenerator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final class RecordSiteMetricHandler
{
    public function __construct(
        private readonly SiteMetricRepositoryInterface $repository,
        private readonly MessageBusInterface           $eventBus,
    ) {}

    public function __invoke(RecordSiteMetricCommand $command): void
    {
        // Validate URL at the application boundary — SiteUrl VO throws on invalid input
        $siteUrl = new SiteUrl($command->siteUrl);

        $id  = UuidGenerator::generate();
        $now = new \DateTimeImmutable();

        $metric = new SiteMetric(
            $id,
            $siteUrl->value(),
            $command->siteName,
            $command->pageViews,
            $command->uniqueVisitors,
            $command->bounceRate,
            $command->loadTimeMs,
            $now,
        );

        $this->repository->save($metric);

        // Raise domain event — consumed async by SiteMetricProjector
        $this->eventBus->dispatch(new SiteMetricRecorded(
            siteMetricId:   $id,
            siteUrl:        $command->siteUrl,
            siteName:       $command->siteName,
            pageViews:      $command->pageViews,
            uniqueVisitors: $command->uniqueVisitors,
            bounceRate:     $command->bounceRate,
            loadTimeMs:     $command->loadTimeMs,
            recordedAt:     $now,
        ));
    }
}
