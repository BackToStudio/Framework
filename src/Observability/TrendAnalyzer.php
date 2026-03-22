<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;

/**
 * Detects degradation trends in metrics over time.
 *
 * Compares the current period's average to the previous period's average
 * and alerts when a metric degrades beyond a configurable threshold.
 *
 * For example, if cache.hit_rate dropped from 95% to 70% (>20% degradation),
 * a warning alert is dispatched.
 */
final class TrendAnalyzer
{
    private const DEFAULT_WINDOW = 3600;        // 1 hour
    private const DEFAULT_THRESHOLD = 20.0;     // 20% degradation
    private const DEFAULT_IMPROVEMENT = -20.0;  // 20% improvement (negative = better)

    private readonly MetricStoreInterface $metricStore;
    private readonly AlertDispatcherInterface $alertDispatcher;

    /** @var array<string, array{direction: string, threshold: float}> */
    private array $watchedMetrics = [];

    public function __construct(
        MetricStoreInterface $metricStore,
        AlertDispatcherInterface $alertDispatcher,
    ) {
        $this->metricStore = $metricStore;
        $this->alertDispatcher = $alertDispatcher;
    }

    /**
     * Watch a metric for degradation trends.
     *
     * @param string $name      Metric name (e.g., 'cache.hit_rate')
     * @param string $direction 'higher_is_better' or 'lower_is_better'
     * @param float  $threshold Percentage change to trigger alert (default: 20.0)
     */
    public function watch(string $name, string $direction = 'higher_is_better', float $threshold = self::DEFAULT_THRESHOLD): void
    {
        $this->watchedMetrics[$name] = [
            'direction' => $direction,
            'threshold' => $threshold,
        ];
    }

    /**
     * Analyze all watched metrics for trends.
     *
     * @param int $window Comparison window in seconds (default: 1 hour)
     * @return array<string, array{trend: string, current_avg: float, previous_avg: float, change_pct: float}>
     */
    public function analyze(int $window = self::DEFAULT_WINDOW): array
    {
        $now = \time();
        $results = [];

        foreach ($this->watchedMetrics as $name => $config) {
            $currentPeriod = $this->metricStore->summary($now - $window);
            $previousPeriod = $this->getWindowSummary($name, $now - (2 * $window), $now - $window);

            if (!isset($currentPeriod[$name]) || $previousPeriod === null) {
                continue;
            }

            $currentAvg = $currentPeriod[$name]['avg'];
            $previousAvg = $previousPeriod['avg'];

            if ($previousAvg == 0.0) {
                continue;
            }

            $changePct = (($currentAvg - $previousAvg) / \abs($previousAvg)) * 100;
            $trend = $this->classifyTrend($changePct, $config['direction'], $config['threshold']);

            $results[$name] = [
                'trend' => $trend,
                'current_avg' => \round($currentAvg, 3),
                'previous_avg' => \round($previousAvg, 3),
                'change_pct' => \round($changePct, 1),
            ];

            if ($trend === 'degrading') {
                $this->alertDispatcher->warning(
                    \sprintf('Metric "%s" degrading: %.1f%% change', $name, $changePct),
                    [
                        'metric' => $name,
                        'current_avg' => $currentAvg,
                        'previous_avg' => $previousAvg,
                        'change_pct' => $changePct,
                        'direction' => $config['direction'],
                    ],
                );
            }
        }

        return $results;
    }

    /**
     * @return array<string, array{direction: string, threshold: float}>
     */
    public function getWatchedMetrics(): array
    {
        return $this->watchedMetrics;
    }

    /**
     * @return array{current: float, min: float, max: float, avg: float, count: int}|null
     */
    private function getWindowSummary(string $name, int $since, int $until): ?array
    {
        $history = $this->metricStore->history($name, $since, $until);

        if ($history === []) {
            return null;
        }

        $values = \array_map(static fn(array $entry): float => $entry['value'], $history);

        return [
            'current' => end($values),
            'min' => \min($values),
            'max' => \max($values),
            'avg' => \array_sum($values) / \count($values),
            'count' => \count($values),
        ];
    }

    private function classifyTrend(float $changePct, string $direction, float $threshold): string
    {
        if ($direction === 'higher_is_better') {
            // Decrease is bad
            if ($changePct <= -$threshold) {
                return 'degrading';
            }
            if ($changePct >= $threshold) {
                return 'improving';
            }
        } else {
            // Increase is bad (e.g., response time, error rate)
            if ($changePct >= $threshold) {
                return 'degrading';
            }
            if ($changePct <= -$threshold) {
                return 'improving';
            }
        }

        return 'stable';
    }
}
