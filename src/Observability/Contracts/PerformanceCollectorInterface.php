<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Port interface for collecting performance metrics.
 */
interface PerformanceCollectorInterface
{
    /**
     * Start timing an operation.
     */
    public function startTimer(string $name): void;

    /**
     * Stop timing an operation and record the duration.
     *
     * @return float Duration in milliseconds
     */
    public function stopTimer(string $name): float;

    /**
     * Increment a counter.
     */
    public function increment(string $name): void;

    /**
     * Get all collected metrics.
     *
     * @return array<string, array{count: int, total_ms: float, avg_ms: float}>
     */
    public function getMetrics(): array;
}
