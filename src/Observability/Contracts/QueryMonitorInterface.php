<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Port interface for monitoring WordPress database queries.
 *
 * Implementations capture query execution data from $wpdb and
 * detect slow queries based on configurable thresholds.
 */
interface QueryMonitorInterface
{
    /**
     * Capture all queries from the current request.
     *
     * Requires SAVEQUERIES to be enabled. Returns an empty array if not.
     *
     * @return array<int, array{sql: string, time: float, caller: string}>
     */
    public function captureQueries(): array;

    /**
     * Get queries that exceeded the slow threshold.
     *
     * @return array<int, array{sql: string, time: float, caller: string}>
     */
    public function getSlowQueries(float $thresholdMs = 50.0): array;

    /**
     * Get query statistics for the current request.
     *
     * @return array{total_queries: int, total_time_ms: float, slow_queries: int, slowest_ms: float}
     */
    public function getStats(float $thresholdMs = 50.0): array;
}
