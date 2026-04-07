<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteMetricRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Write model — one row per metric recording for a site.
 * The read model (DashboardReadEntry) is projected from these records asynchronously.
 */
#[ORM\Entity(repositoryClass: SiteMetricRepository::class)]
#[ORM\Table(name: 'site_metrics')]
#[ORM\Index(columns: ['site_url'], name: 'idx_sm_site_url')]
#[ORM\Index(columns: ['recorded_at'], name: 'idx_sm_recorded_at')]
class SiteMetric
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(length: 191)]
    private string $siteUrl;

    #[ORM\Column(length: 255)]
    private string $siteName;

    #[ORM\Column(type: 'integer')]
    private int $pageViews;

    #[ORM\Column(type: 'integer')]
    private int $uniqueVisitors;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $bounceRate;

    #[ORM\Column(type: 'integer')]
    private int $loadTimeMs;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $recordedAt;

    public function __construct(
        string             $id,
        string             $siteUrl,
        string             $siteName,
        int                $pageViews,
        int                $uniqueVisitors,
        float              $bounceRate,
        int                $loadTimeMs,
        \DateTimeImmutable $recordedAt,
    ) {
        $this->id             = $id;
        $this->siteUrl        = $siteUrl;
        $this->siteName       = $siteName;
        $this->pageViews      = $pageViews;
        $this->uniqueVisitors = $uniqueVisitors;
        $this->bounceRate     = (string) $bounceRate;
        $this->loadTimeMs     = $loadTimeMs;
        $this->recordedAt     = $recordedAt;
    }

    public function getId(): string { return $this->id; }

    public function getSiteUrl(): string { return $this->siteUrl; }

    public function getSiteName(): string { return $this->siteName; }

    public function getPageViews(): int { return $this->pageViews; }

    public function getUniqueVisitors(): int { return $this->uniqueVisitors; }

    public function getBounceRate(): float { return (float) $this->bounceRate; }

    public function getLoadTimeMs(): int { return $this->loadTimeMs; }

    public function getRecordedAt(): \DateTimeImmutable { return $this->recordedAt; }
}
