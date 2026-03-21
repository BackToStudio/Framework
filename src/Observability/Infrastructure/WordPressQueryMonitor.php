<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Infrastructure;

use BackTo\Framework\Observability\Contracts\QueryMonitorInterface;

/**
 * WordPress $wpdb query monitor.
 *
 * Reads the queries logged by WordPress when SAVEQUERIES is enabled
 * and provides slow query detection and statistics.
 */
final class WordPressQueryMonitor implements QueryMonitorInterface
{
    public function captureQueries(): array
    {
        global $wpdb;

        if (!isset($wpdb->queries) || !\is_array($wpdb->queries)) {
            return [];
        }

        $queries = [];
        foreach ($wpdb->queries as $query) {
            if (!\is_array($query) || \count($query) < 3) {
                continue;
            }

            $queries[] = [
                'sql' => (string) $query[0],
                'time' => (float) $query[1] * 1000, // seconds → milliseconds
                'caller' => (string) $query[2],
            ];
        }

        return $queries;
    }

    public function getSlowQueries(float $thresholdMs = 50.0): array
    {
        $queries = $this->captureQueries();

        return \array_values(\array_filter(
            $queries,
            static fn(array $q): bool => $q['time'] >= $thresholdMs,
        ));
    }

    public function getStats(float $thresholdMs = 50.0): array
    {
        $queries = $this->captureQueries();
        $totalTime = 0.0;
        $slowCount = 0;
        $slowest = 0.0;

        foreach ($queries as $q) {
            $totalTime += $q['time'];
            if ($q['time'] >= $thresholdMs) {
                $slowCount++;
            }
            if ($q['time'] > $slowest) {
                $slowest = $q['time'];
            }
        }

        return [
            'total_queries' => \count($queries),
            'total_time_ms' => \round($totalTime, 3),
            'slow_queries' => $slowCount,
            'slowest_ms' => \round($slowest, 3),
        ];
    }
}
