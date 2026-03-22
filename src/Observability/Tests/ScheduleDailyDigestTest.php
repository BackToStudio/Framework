<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\HealthCheckRegistry;
use BackTo\Framework\Observability\ScheduleDailyDigest;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;
use PHPUnit\Framework\TestCase;

final class ScheduleDailyDigestTest extends TestCase
{
    public function test_send_digest_dispatches_info_when_all_healthy(): void
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('cache');
        $check->method('check')->willReturn(HealthCheckResult::healthy('OK'));

        $registry = new HealthCheckRegistry();
        $registry->add($check);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $metricStore->method('summary')->willReturn([]);

        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('info', 'Daily operations digest', $this->callback(function (array $context) {
                return str_contains($context['health_summary'], '1 healthy, 0 degraded, 0 unhealthy');
            }));

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $cronScheduler = $this->createMock(CronSchedulerInterface::class);

        $digest = new ScheduleDailyDigest($registry, $metricStore, $alertDispatcher, $hookDispatcher, $cronScheduler);
        $digest->sendDigest();
    }

    public function test_send_digest_dispatches_critical_when_unhealthy(): void
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('db');
        $check->method('check')->willReturn(HealthCheckResult::unhealthy('DB down'));

        $registry = new HealthCheckRegistry();
        $registry->add($check);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $metricStore->method('summary')->willReturn([]);

        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('critical', 'Daily operations digest', $this->anything());

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $cronScheduler = $this->createMock(CronSchedulerInterface::class);

        $digest = new ScheduleDailyDigest($registry, $metricStore, $alertDispatcher, $hookDispatcher, $cronScheduler);
        $digest->sendDigest();
    }

    public function test_send_digest_dispatches_warning_when_degraded(): void
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('queue');
        $check->method('check')->willReturn(HealthCheckResult::degraded('High depth'));

        $registry = new HealthCheckRegistry();
        $registry->add($check);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $metricStore->method('summary')->willReturn([]);

        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('warning', 'Daily operations digest', $this->anything());

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $cronScheduler = $this->createMock(CronSchedulerInterface::class);

        $digest = new ScheduleDailyDigest($registry, $metricStore, $alertDispatcher, $hookDispatcher, $cronScheduler);
        $digest->sendDigest();
    }

    public function test_ensure_scheduled_registers_cron(): void
    {
        $registry = new HealthCheckRegistry();
        $metricStore = $this->createMock(MetricStoreInterface::class);
        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);

        $cronScheduler = $this->createMock(CronSchedulerInterface::class);
        $cronScheduler->method('isScheduled')->willReturn(false);
        $cronScheduler->expects($this->once())
            ->method('scheduleRecurring')
            ->with('backto_daily_digest', 'daily');

        $digest = new ScheduleDailyDigest($registry, $metricStore, $alertDispatcher, $hookDispatcher, $cronScheduler);
        $digest->ensureScheduled();
    }

    public function test_includes_metric_highlights_in_digest(): void
    {
        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('cache');
        $check->method('check')->willReturn(HealthCheckResult::healthy('OK'));

        $registry = new HealthCheckRegistry();
        $registry->add($check);

        $metricStore = $this->createMock(MetricStoreInterface::class);
        $metricStore->method('summary')->willReturn([
            'cache.hit_rate' => ['current' => 95.0, 'min' => 80.0, 'max' => 99.0, 'avg' => 92.5, 'count' => 24],
        ]);

        $alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $alertDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('info', 'Daily operations digest', $this->callback(function (array $context) {
                return $context['metrics_tracked'] === 1
                    && isset($context['metric_highlights']['cache.hit_rate']);
            }));

        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $cronScheduler = $this->createMock(CronSchedulerInterface::class);

        $digest = new ScheduleDailyDigest($registry, $metricStore, $alertDispatcher, $hookDispatcher, $cronScheduler);
        $digest->sendDigest();
    }
}
