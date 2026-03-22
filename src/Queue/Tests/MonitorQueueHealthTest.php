<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Tests;

use BackTo\Framework\Contracts\HealthCheckStatus;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Queue\Contracts\QueueQueryInterface;
use BackTo\Framework\Queue\Entity\JobStatus;
use BackTo\Framework\Queue\MonitorQueueHealth;
use PHPUnit\Framework\TestCase;

final class MonitorQueueHealthTest extends TestCase
{
    public function test_healthy_when_queue_is_normal(): void
    {
        $query = $this->createMock(QueueQueryInterface::class);
        $query->method('countByStatus')->willReturnMap([
            [JobStatus::Pending, 5],
            [JobStatus::Running, 2],
            [JobStatus::Failed, 0],
        ]);
        $query->method('getActiveGroups')->willReturn(['default']);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->never())->method('warning');

        $monitor = new MonitorQueueHealth($query, $metricStore, $alertDispatcher);
        $result = $monitor->check();

        $this->assertSame(HealthCheckStatus::Healthy->value, $result->getStatus());
    }

    public function test_degraded_when_too_many_failed_jobs(): void
    {
        $query = $this->createMock(QueueQueryInterface::class);
        $query->method('countByStatus')->willReturnMap([
            [JobStatus::Pending, 5],
            [JobStatus::Running, 1],
            [JobStatus::Failed, 15],
        ]);
        $query->method('getActiveGroups')->willReturn([]);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->once())->method('warning');

        $monitor = new MonitorQueueHealth($query, $metricStore, $alertDispatcher);
        $result = $monitor->check();

        $this->assertSame(HealthCheckStatus::Degraded->value, $result->getStatus());
    }

    public function test_degraded_when_high_pending_count(): void
    {
        $query = $this->createMock(QueueQueryInterface::class);
        $query->method('countByStatus')->willReturnMap([
            [JobStatus::Pending, 200],
            [JobStatus::Running, 1],
            [JobStatus::Failed, 0],
        ]);
        $query->method('getActiveGroups')->willReturn(['default']);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->once())->method('warning');

        $monitor = new MonitorQueueHealth($query, $metricStore, $alertDispatcher);
        $result = $monitor->check();

        $this->assertSame(HealthCheckStatus::Degraded->value, $result->getStatus());
    }

    public function test_records_metrics(): void
    {
        $query = $this->createMock(QueueQueryInterface::class);
        $query->method('countByStatus')->willReturn(0);
        $query->method('getActiveGroups')->willReturn([]);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $metricStore->expects($this->exactly(4))
            ->method('record');

        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);

        $monitor = new MonitorQueueHealth($query, $metricStore, $alertDispatcher);
        $monitor->check();
    }

    public function test_name_is_queue(): void
    {
        $query = $this->createMock(QueueQueryInterface::class);
        $metricStore = $this->createMock(MetricStoreInterface::class);
        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);

        $monitor = new MonitorQueueHealth($query, $metricStore, $alertDispatcher);

        $this->assertSame('queue', $monitor->getName());
    }
}
