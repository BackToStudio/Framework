<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Observability\Contracts\PerformanceCollectorInterface;

/**
 * In-memory performance metrics collector.
 *
 * Tracks timing and counters for framework operations.
 * Integrates with Query Monitor via the 'qm/collect' filter when available.
 */
final class PerformanceCollector implements PerformanceCollectorInterface
{
    /** @var array<string, float> */
    private array $timers = [];

    /** @var array<string, array{count: int, total_ms: float}> */
    private array $metrics = [];

    public function startTimer(string $name): void
    {
        $this->timers[$name] = \hrtime(true);
    }

    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }

        $durationNs = \hrtime(true) - $this->timers[$name];
        $durationMs = $durationNs / 1_000_000;
        unset($this->timers[$name]);

        if (!isset($this->metrics[$name])) {
            $this->metrics[$name] = ['count' => 0, 'total_ms' => 0.0];
        }

        $this->metrics[$name]['count']++;
        $this->metrics[$name]['total_ms'] += $durationMs;

        return $durationMs;
    }

    public function increment(string $name): void
    {
        if (!isset($this->metrics[$name])) {
            $this->metrics[$name] = ['count' => 0, 'total_ms' => 0.0];
        }

        $this->metrics[$name]['count']++;
    }

    /**
     * @return array<string, array{count: int, total_ms: float, avg_ms: float}>
     */
    public function getMetrics(): array
    {
        $result = [];

        foreach ($this->metrics as $name => $data) {
            $result[$name] = [
                'count' => $data['count'],
                'total_ms' => \round($data['total_ms'], 3),
                'avg_ms' => $data['count'] > 0 ? \round($data['total_ms'] / $data['count'], 3) : 0.0,
            ];
        }

        return $result;
    }
}
