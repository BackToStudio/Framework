<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Port interface for persisting metrics beyond a single request lifecycle.
 *
 * Implementations store aggregated metrics (counters, gauges, timers)
 * so they can be queried for dashboards, alerting, and trending.
 */
interface MetricStoreInterface
{
    /**
     * Record a metric data point.
     *
     * @param string $name   Metric identifier (e.g., 'cache.hits', 'queue.depth')
     * @param float  $value  Metric value
     * @param string $type   One of: counter, gauge, timer
     * @param array<string, string> $tags  Optional dimensional tags
     */
    public function record(string $name, float $value, string $type = 'gauge', array $tags = []): void;

    /**
     * Increment a counter by the given amount.
     */
    public function increment(string $name, float $amount = 1.0, array $tags = []): void;

    /**
     * Get the latest value for a metric.
     *
     * @return array{value: float, type: string, recorded_at: int}|null
     */
    public function latest(string $name): ?array;

    /**
     * Get metric history for a given time range.
     *
     * @param int $since Unix timestamp
     * @param int $until Unix timestamp (0 = now)
     * @return array<int, array{name: string, value: float, type: string, tags: array<string, string>, recorded_at: int}>
     */
    public function history(string $name, int $since, int $until = 0): array;

    /**
     * Get a summary of all metrics recorded since a given timestamp.
     *
     * @return array<string, array{current: float, min: float, max: float, avg: float, count: int}>
     */
    public function summary(int $since): array;

    /**
     * Purge metrics older than the given number of days.
     */
    public function purge(int $days = 30): int;
}
