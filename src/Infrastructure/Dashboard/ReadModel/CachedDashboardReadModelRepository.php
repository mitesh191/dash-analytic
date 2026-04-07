<?php

declare(strict_types=1);

namespace App\Infrastructure\Dashboard\ReadModel;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Decorator that wraps the DBAL read model with a PSR-6 cache layer.
 * TTL is intentionally short (60 s) so that async projector updates
 * get surfaced quickly without hammering the database on every request.
 */
final class CachedDashboardReadModelRepository implements DashboardReadModelRepositoryInterface
{
    private const TTL_SECONDS = 60;

    public function __construct(
        private readonly DashboardReadModelRepositoryInterface $inner,
        private readonly CacheItemPoolInterface                $cache,
    ) {}

    public function findPaginated(
        int     $page    = 1,
        int     $limit   = 50,
        ?string $status  = null,
        string  $sortBy  = 'total_page_views',
        string  $sortDir = 'DESC',
    ): array {
        $cacheKey = sprintf(
            'dashboard_p%d_l%d_s%s_%s_%s',
            $page,
            $limit,
            $status ?? 'all',
            $sortBy,
            strtolower($sortDir),
        );

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $cached = $item->get();
            $cached['_meta']['cache_hit'] = true;
            $cached['_meta']['source']    = 'cache';
            return $cached;
        }

        $result = $this->inner->findPaginated($page, $limit, $status, $sortBy, $sortDir);

        $item->set($result)->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($item);

        return $result;
    }
}
