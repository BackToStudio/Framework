<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Observability\PerformanceCollector;
use PHPUnit\Framework\TestCase;

class PerformanceCollectorTest extends TestCase
{
    public function testTimerRecordsDuration(): void
    {
        $collector = new PerformanceCollector();

        $collector->startTimer('test');
        // Small busy-wait to ensure measurable duration
        $start = \hrtime(true);
        while ((\hrtime(true) - $start) < 100_000) {
            // ~0.1ms
        }
        $duration = $collector->stopTimer('test');

        $this->assertGreaterThan(0.0, $duration);
    }

    public function testStopTimerReturnsZeroForUnknownTimer(): void
    {
        $collector = new PerformanceCollector();

        $this->assertSame(0.0, $collector->stopTimer('unknown'));
    }

    public function testIncrementTracksCount(): void
    {
        $collector = new PerformanceCollector();

        $collector->increment('hooks.registered');
        $collector->increment('hooks.registered');
        $collector->increment('hooks.registered');

        $metrics = $collector->getMetrics();

        $this->assertSame(3, $metrics['hooks.registered']['count']);
        $this->assertSame(0.0, $metrics['hooks.registered']['total_ms']);
    }

    public function testGetMetricsCalculatesAverage(): void
    {
        $collector = new PerformanceCollector();

        $collector->startTimer('op');
        $collector->stopTimer('op');
        $collector->startTimer('op');
        $collector->stopTimer('op');

        $metrics = $collector->getMetrics();

        $this->assertSame(2, $metrics['op']['count']);
        $this->assertArrayHasKey('avg_ms', $metrics['op']);
    }

    public function testEmptyMetrics(): void
    {
        $collector = new PerformanceCollector();

        $this->assertSame([], $collector->getMetrics());
    }
}
