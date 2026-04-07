<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DashboardReadEntryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Denormalized read model projected from SiteMetric events.
 * One row per unique site URL — pre-aggregated for O(1) dashboard queries.
 * Never mutated directly by business logic; only updated by SiteMetricProjector.
 */
#[ORM\Entity(repositoryClass: DashboardReadEntryRepository::class)]
#[ORM\Table(name: 'dashboard_read_entries')]
#[ORM\Index(columns: ['status'], name: 'idx_dre_status')]
#[ORM\Index(columns: ['total_page_views'], name: 'idx_dre_total_page_views')]
#[ORM\Index(columns: ['unique_visitors'], name: 'idx_dre_unique_visitors')]
#[ORM\Index(columns: ['last_recorded_at'], name: 'idx_dre_last_recorded_at')]
class DashboardReadEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(length: 191, unique: true)]
    private string $siteUrl;

    #[ORM\Column(length: 255)]
    private string $siteName;

    /** Accumulated across all recordings for this site. */
    #[ORM\Column(type: 'bigint')]
    private string $totalPageViews;

    #[ORM\Column(type: 'integer')]
    private int $uniqueVisitors;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    private string $bounceRate;

    /** Running average updated on each metric record. */
    #[ORM\Column(type: 'integer')]
    private int $avgLoadTimeMs;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastRecordedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string             $id,
        string             $siteUrl,
        string             $siteName,
        int                $totalPageViews,
        int                $uniqueVisitors,
        float              $bounceRate,
        int                $avgLoadTimeMs,
        string             $status,
        \DateTimeImmutable $lastRecordedAt,
    ) {
        $this->id             = $id;
        $this->siteUrl        = $siteUrl;
        $this->siteName       = $siteName;
        $this->totalPageViews = (string) $totalPageViews;
        $this->uniqueVisitors = $uniqueVisitors;
        $this->bounceRate     = (string) $bounceRate;
        $this->avgLoadTimeMs  = $avgLoadTimeMs;
        $this->status         = $status;
        $this->lastRecordedAt = $lastRecordedAt;
        $this->updatedAt      = new \DateTimeImmutable();
    }

    public function updateFromMetric(
        int                $additionalPageViews,
        int                $uniqueVisitors,
        float              $newBounceRate,
        int                $newLoadTimeMs,
        string             $status,
        \DateTimeImmutable $recordedAt,
    ): void {
        $this->totalPageViews = (string) ((int) $this->totalPageViews + $additionalPageViews);
        $this->uniqueVisitors = $uniqueVisitors;
        $this->bounceRate     = (string) $newBounceRate;
        $this->avgLoadTimeMs  = (int) round(($this->avgLoadTimeMs + $newLoadTimeMs) / 2);
        $this->status         = $status;
        $this->lastRecordedAt = $recordedAt;
        $this->updatedAt      = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }

    public function getSiteUrl(): string { return $this->siteUrl; }

    public function getSiteName(): string { return $this->siteName; }

    public function getTotalPageViews(): int { return (int) $this->totalPageViews; }

    public function getUniqueVisitors(): int { return $this->uniqueVisitors; }

    public function getBounceRate(): float { return (float) $this->bounceRate; }

    public function getAvgLoadTimeMs(): int { return $this->avgLoadTimeMs; }

    public function getStatus(): string { return $this->status; }

    public function getLastRecordedAt(): \DateTimeImmutable { return $this->lastRecordedAt; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
