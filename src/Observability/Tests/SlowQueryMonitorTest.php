<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\Contracts\QueryMonitorInterface;
use BackTo\Framework\Observability\SlowQueryMonitor;
use PHPUnit\Framework\TestCase;

final class SlowQueryMonitorTest extends TestCase
{
    private QueryMonitorInterface $queryMonitor;
    private MetricStoreInterface $metricStore;
    private AlertDispatcherInterface $alertDispatcher;
    private LoggerInterface $logger;
    private HookDispatcherInterface $hookDispatcher;

    protected function setUp(): void
    {
        $this->queryMonitor = $this->createMock(QueryMonitorInterface::class);
        $this->metricStore = $this->createMock(MetricStoreInterface::class);
        $this->alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    public function test_records_query_metrics(): void
    {
        $this->queryMonitor->method('getStats')->willReturn([
            'total_queries' => 25,
            'total_time_ms' => 150.5,
            'slow_queries' => 2,
            'slowest_ms' => 80.3,
        ]);

        // 4 records: total_queries, total_time_ms, slow_queries, slowest_ms
        $this->metricStore->expects($this->exactly(4))
            ->method('record');

        $this->alertDispatcher->expects($this->never())->method('warning');

        $monitor = new SlowQueryMonitor(
            $this->queryMonitor,
            $this->metricStore,
            $this->alertDispatcher,
            $this->logger,
            $this->hookDispatcher,
        );
        $monitor->analyze();
    }

    public function test_alerts_when_many_slow_queries(): void
    {
        $this->queryMonitor->method('getStats')->willReturn([
            'total_queries' => 50,
            'total_time_ms' => 500.0,
            'slow_queries' => 8,
            'slowest_ms' => 200.0,
        ]);
        $this->queryMonitor->method('getSlowQueries')->willReturn([
            ['sql' => 'SELECT * FROM wp_posts', 'time' => 200.0, 'caller' => 'WP_Query'],
            ['sql' => 'SELECT * FROM wp_options', 'time' => 150.0, 'caller' => 'get_option'],
        ]);

        $this->alertDispatcher->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('8 slow queries'));

        $monitor = new SlowQueryMonitor(
            $this->queryMonitor,
            $this->metricStore,
            $this->alertDispatcher,
            $this->logger,
            $this->hookDispatcher,
        );
        $monitor->analyze();
    }

    public function test_no_alert_when_below_threshold(): void
    {
        $this->queryMonitor->method('getStats')->willReturn([
            'total_queries' => 10,
            'total_time_ms' => 50.0,
            'slow_queries' => 3,
            'slowest_ms' => 60.0,
        ]);

        $this->alertDispatcher->expects($this->never())->method('warning');

        $monitor = new SlowQueryMonitor(
            $this->queryMonitor,
            $this->metricStore,
            $this->alertDispatcher,
            $this->logger,
            $this->hookDispatcher,
        );
        $monitor->analyze();
    }

    public function test_skips_when_no_queries(): void
    {
        $this->queryMonitor->method('getStats')->willReturn([
            'total_queries' => 0,
            'total_time_ms' => 0.0,
            'slow_queries' => 0,
            'slowest_ms' => 0.0,
        ]);

        $this->metricStore->expects($this->never())->method('record');

        $monitor = new SlowQueryMonitor(
            $this->queryMonitor,
            $this->metricStore,
            $this->alertDispatcher,
            $this->logger,
            $this->hookDispatcher,
        );
        $monitor->analyze();
    }

    public function test_logs_warning_on_slow_queries(): void
    {
        $this->queryMonitor->method('getStats')->willReturn([
            'total_queries' => 10,
            'total_time_ms' => 100.0,
            'slow_queries' => 2,
            'slowest_ms' => 75.0,
        ]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('2 slow queries'));

        $monitor = new SlowQueryMonitor(
            $this->queryMonitor,
            $this->metricStore,
            $this->alertDispatcher,
            $this->logger,
            $this->hookDispatcher,
        );
        $monitor->analyze();
    }

    public function test_custom_threshold(): void
    {
        $this->queryMonitor->expects($this->once())
            ->method('getStats')
            ->with(100.0)
            ->willReturn([
                'total_queries' => 10,
                'total_time_ms' => 50.0,
                'slow_queries' => 0,
                'slowest_ms' => 0.0,
            ]);

        $this->alertDispatcher->expects($this->never())->method('warning');

        $monitor = new SlowQueryMonitor(
            $this->queryMonitor,
            $this->metricStore,
            $this->alertDispatcher,
            $this->logger,
            $this->hookDispatcher,
            100.0,
        );
        $monitor->analyze();
    }
}
