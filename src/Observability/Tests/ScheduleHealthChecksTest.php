<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Observability\AutoRemediation;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\HealthCheckRegistry;
use BackTo\Framework\Observability\ScheduleHealthChecks;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;
use PHPUnit\Framework\TestCase;

final class ScheduleHealthChecksTest extends TestCase
{
    private AutoRemediation $autoRemediation;

    protected function setUp(): void
    {
        $this->autoRemediation = new AutoRemediation(
            $this->createMock(\BackTo\Framework\Contracts\LoggerInterface::class),
            $this->createMock(AlertDispatcherInterface::class),
            $this->createMock(MetricStoreInterface::class),
        );
    }

    public function test_run_checks_records_metrics_and_alerts_on_unhealthy(): void
    {
        $healthyCheck = $this->createMock(HealthCheckInterface::class);
        $healthyCheck->method('getName')->willReturn('db');
        $healthyCheck->method('check')->willReturn(HealthCheckResult::healthy('OK'));

        $unhealthyCheck = $this->createMock(HealthCheckInterface::class);
        $unhealthyCheck->method('getName')->willReturn('cache');
        $unhealthyCheck->method('check')->willReturn(HealthCheckResult::unhealthy('Cache down'));

        $registry = new HealthCheckRegistry();
        $registry->add($healthyCheck);
        $registry->add($unhealthyCheck);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $metricStore->expects($this->exactly(3))
            ->method('record');

        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('1 health check(s) unhealthy'));

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $cronScheduler = $this->createMock(CronSchedulerInterface::class);

        $scheduler = new ScheduleHealthChecks(
            $registry,
            $metricStore,
            $alertDispatcher,
            $hookDispatcher,
            $cronScheduler,
            $this->autoRemediation,
        );

        $scheduler->runChecks();
    }

    public function test_run_checks_no_alert_when_all_healthy(): void
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('db');
        $check->method('check')->willReturn(HealthCheckResult::healthy('OK'));

        $registry = new HealthCheckRegistry();
        $registry->add($check);

        $metricStore = $this->createMock(MetricStoreInterface::class);

        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->never())->method('critical');
        $alertDispatcher->expects($this->never())->method('warning');

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $cronScheduler = $this->createMock(CronSchedulerInterface::class);

        $scheduler = new ScheduleHealthChecks(
            $registry,
            $metricStore,
            $alertDispatcher,
            $hookDispatcher,
            $cronScheduler,
            $this->autoRemediation,
        );

        $scheduler->runChecks();
    }

    public function test_ensure_scheduled_calls_cron_scheduler(): void
    {
        $registry = new HealthCheckRegistry();
        $metricStore = $this->createMock(MetricStoreInterface::class);
        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);

        $cronScheduler = $this->createMock(CronSchedulerInterface::class);
        $cronScheduler->method('isScheduled')->willReturn(false);
        $cronScheduler->expects($this->once())
            ->method('scheduleRecurring')
            ->with('backto_health_check', 'hourly');

        $scheduler = new ScheduleHealthChecks(
            $registry,
            $metricStore,
            $alertDispatcher,
            $hookDispatcher,
            $cronScheduler,
            $this->autoRemediation,
        );

        $scheduler->ensureScheduled();
    }
}
