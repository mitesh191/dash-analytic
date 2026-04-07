<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use App\Application\Dashboard\Query\GetDashboardEntries\DashboardEntryView;

/**
 * Runs threshold checks against the current page of dashboard entries
 * and returns a list of monitoring hook results the UI can display.
 *
 * Each check represents a "monitoring hook" — a named test with a pass/fail
 * outcome, the measured value, and the threshold that triggered it.
 */
final class MonitoringChecks
{
    private const SLOW_LOAD_THRESHOLD_MS  = 3_000;
    private const HIGH_BOUNCE_THRESHOLD   = 70.0;   // percent
    private const DEGRADED_SITE_LIMIT     = 5;       // max degraded sites per page before alert
    private const SLOW_QUERY_THRESHOLD_MS = 500;

    /**
     * @param  DashboardEntryView[] $entries
     * @param  array{source: string, cache_hit: bool, query_time_ms: int, is_slow: bool} $meta
     * @return array<int, array{name: string, status: string, value: string, threshold: string, detail: string}>
     */
    public function run(array $entries, array $meta): array
    {
        $checks = [];

        // Hook 1: Query performance
        $checks[] = [
            'name'      => 'Query Response Time',
            'status'    => $meta['query_time_ms'] < self::SLOW_QUERY_THRESHOLD_MS ? 'pass' : 'fail',
            'value'     => $meta['query_time_ms'] . ' ms',
            'threshold' => self::SLOW_QUERY_THRESHOLD_MS . ' ms',
            'detail'    => $meta['cache_hit'] ? 'Served from cache — no DB query executed' : 'Served from database',
        ];

        // Hook 2: Count degraded sites on current page
        $degradedCount = count(array_filter($entries, fn(DashboardEntryView $e) => $e->status === 'degraded'));
        $checks[] = [
            'name'      => 'Degraded Sites (this page)',
            'status'    => $degradedCount <= self::DEGRADED_SITE_LIMIT ? 'pass' : 'warn',
            'value'     => (string) $degradedCount . ' sites',
            'threshold' => '<= ' . self::DEGRADED_SITE_LIMIT . ' sites',
            'detail'    => $degradedCount > 0 ? "{$degradedCount} site(s) reporting degraded status" : 'All sites healthy',
        ];

        // Hook 3: High bounce rate — how many sites exceed threshold
        $highBounce = array_filter($entries, fn(DashboardEntryView $e) => $e->bounceRate >= self::HIGH_BOUNCE_THRESHOLD);
        $highBounceCount = count($highBounce);
        $checks[] = [
            'name'      => 'High Bounce Rate Sites',
            'status'    => $highBounceCount === 0 ? 'pass' : 'warn',
            'value'     => (string) $highBounceCount . ' sites',
            'threshold' => '>= ' . self::HIGH_BOUNCE_THRESHOLD . '%',
            'detail'    => $highBounceCount > 0 ? "{$highBounceCount} site(s) with bounce rate ≥ " . self::HIGH_BOUNCE_THRESHOLD . '%' : 'No high bounce rate sites',
        ];

        // Hook 4: Slow average load times across this page
        $slowSites = array_filter($entries, fn(DashboardEntryView $e) => $e->avgLoadTimeMs >= self::SLOW_LOAD_THRESHOLD_MS);
        $slowCount = count($slowSites);
        $checks[] = [
            'name'      => 'Slow Avg Load Time Sites',
            'status'    => $slowCount === 0 ? 'pass' : 'warn',
            'value'     => (string) $slowCount . ' sites',
            'threshold' => '>= ' . self::SLOW_LOAD_THRESHOLD_MS . ' ms',
            'detail'    => $slowCount > 0 ? "{$slowCount} site(s) with avg load ≥ " . number_format(self::SLOW_LOAD_THRESHOLD_MS) . ' ms' : 'All sites loading within threshold',
        ];

        // Hook 5: Cache layer health
        $checks[] = [
            'name'      => 'Cache Layer',
            'status'    => 'pass',
            'value'     => $meta['cache_hit'] ? 'HIT' : 'MISS (first load)',
            'threshold' => 'TTL 60 s',
            'detail'    => $meta['cache_hit'] ? 'Response served from PSR-6 cache — DB not queried' : 'Cache cold or expired — result stored for next 60 s',
        ];

        return $checks;
    }
}
