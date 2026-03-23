<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\Contracts\QueryMonitorInterface;

/**
 * Tracks database query performance and alerts on slow queries.
 *
 * Hooks into WordPress shutdown to capture query statistics from the
 * current request. Records metrics and dispatches warnings when slow
 * queries are detected.
 *
 * Requires SAVEQUERIES to be enabled (typically in development/staging,
 * or selectively in production for diagnostics).
 */
final class SlowQueryMonitor implements Hooks
{
    private const DEFAULT_THRESHOLD_MS = 50.0;
    private const ALERT_THRESHOLD = 5;

    private readonly QueryMonitorInterface $queryMonitor;
    private readonly MetricStoreInterface $metricStore;
    private readonly AlertDispatcherInterface $alertDispatcher;
    private readonly LoggerInterface $logger;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly float $thresholdMs;

    public function __construct(
        QueryMonitorInterface $queryMonitor,
        MetricStoreInterface $metricStore,
        AlertDispatcherInterface $alertDispatcher,
        LoggerInterface $logger,
        HookDispatcherInterface $hookDispatcher,
        float $thresholdMs = self::DEFAULT_THRESHOLD_MS,
    ) {
        if ($thresholdMs <= 0.0) {
            throw new \InvalidArgumentException('Slow query threshold must be positive.');
        }

        $this->queryMonitor = $queryMonitor;
        $this->metricStore = $metricStore;
        $this->alertDispatcher = $alertDispatcher;
        $this->logger = $logger;
        $this->hookDispatcher = $hookDispatcher;
        $this->thresholdMs = $thresholdMs;
    }

    public function hooks(): void
    {
        if (!$this->isSaveQueriesEnabled()) {
            return;
        }

        $this->hookDispatcher->addAction('shutdown', [$this, 'analyze'], 998);
    }

    public function analyze(): void
    {
        $stats = $this->queryMonitor->getStats($this->thresholdMs);

        if ($stats['total_queries'] === 0) {
            return;
        }

        // Record per-request query metrics
        $this->metricStore->record('db.total_queries', (float) $stats['total_queries'], 'gauge');
        $this->metricStore->record('db.total_time_ms', $stats['total_time_ms'], 'gauge');
        $this->metricStore->record('db.slow_queries', (float) $stats['slow_queries'], 'gauge');

        if ($stats['slowest_ms'] > 0) {
            $this->metricStore->record('db.slowest_ms', $stats['slowest_ms'], 'gauge');
        }

        // Alert if too many slow queries
        if ($stats['slow_queries'] >= self::ALERT_THRESHOLD) {
            $slowQueries = $this->queryMonitor->getSlowQueries($this->thresholdMs);
            $top3 = \array_slice($slowQueries, 0, 3);

            $this->alertDispatcher->warning(
                \sprintf(
                    '%d slow queries detected (>%.0fms), slowest: %.1fms',
                    $stats['slow_queries'],
                    $this->thresholdMs,
                    $stats['slowest_ms'],
                ),
                [
                    'total_queries' => $stats['total_queries'],
                    'total_time_ms' => $stats['total_time_ms'],
                    'top_slow_queries' => \array_map(
                        static fn(array $q): string => \sprintf(
                            '%.1fms: %s',
                            $q['time'],
                            \substr($q['sql'], 0, 200),
                        ),
                        $top3,
                    ),
                ],
            );
        }

        // Log individual slow queries for debugging
        if ($stats['slow_queries'] > 0) {
            $this->logger->warning(\sprintf(
                'Request had %d slow queries (>%.0fms), total DB time: %.1fms',
                $stats['slow_queries'],
                $this->thresholdMs,
                $stats['total_time_ms'],
            ));
        }
    }

    protected function isSaveQueriesEnabled(): bool
    {
        return \defined('SAVEQUERIES') && SAVEQUERIES;
    }
}
