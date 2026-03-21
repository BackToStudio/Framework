<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Contracts\HealthCheckStatus;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;

/**
 * Schedules automated health checks via WP-Cron and records results.
 *
 * Runs all registered health checks every hour, persists results
 * as metrics, and dispatches alerts for degraded/unhealthy states.
 */
final class ScheduleHealthChecks implements Hooks
{
    private const CRON_HOOK = 'backto_health_check';
    private const SCHEDULE = 'hourly';

    private readonly HealthCheckRegistry $registry;
    private readonly MetricStoreInterface $metricStore;
    private readonly AlertDispatcherInterface $alertDispatcher;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly CronSchedulerInterface $cronScheduler;

    public function __construct(
        HealthCheckRegistry $registry,
        MetricStoreInterface $metricStore,
        AlertDispatcherInterface $alertDispatcher,
        HookDispatcherInterface $hookDispatcher,
        CronSchedulerInterface $cronScheduler,
    ) {
        $this->registry = $registry;
        $this->metricStore = $metricStore;
        $this->alertDispatcher = $alertDispatcher;
        $this->hookDispatcher = $hookDispatcher;
        $this->cronScheduler = $cronScheduler;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'ensureScheduled']);
        $this->hookDispatcher->addAction(self::CRON_HOOK, [$this, 'runChecks']);
    }

    public function ensureScheduled(): void
    {
        if (!$this->cronScheduler->isScheduled(self::CRON_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::CRON_HOOK, self::SCHEDULE);
        }
    }

    public function runChecks(): void
    {
        $results = $this->registry->runAll();
        $unhealthy = [];
        $degraded = [];

        foreach ($results as $name => $result) {
            $statusValue = $this->statusToNumeric($result);
            $this->metricStore->record('health_check.' . $name, $statusValue, 'gauge', [
                'status' => $result->getStatus(),
            ]);

            if ($result->getHealthCheckStatus() === HealthCheckStatus::Unhealthy) {
                $unhealthy[$name] = $result->getMessage();
            } elseif ($result->getHealthCheckStatus() === HealthCheckStatus::Degraded) {
                $degraded[$name] = $result->getMessage();
            }
        }

        // Record overall health score (0-100)
        $total = \count($results);
        $healthyCount = $total - \count($unhealthy) - \count($degraded);
        $score = $total > 0 ? \round(($healthyCount / $total) * 100, 1) : 100.0;
        $this->metricStore->record('health_check.score', $score, 'gauge');

        if ($unhealthy !== []) {
            $this->alertDispatcher->critical(
                \sprintf('%d health check(s) unhealthy', \count($unhealthy)),
                ['checks' => $unhealthy],
            );
        }

        if ($degraded !== []) {
            $this->alertDispatcher->warning(
                \sprintf('%d health check(s) degraded', \count($degraded)),
                ['checks' => $degraded],
            );
        }
    }

    private function statusToNumeric(HealthCheckResult $result): float
    {
        return match ($result->getHealthCheckStatus()) {
            HealthCheckStatus::Healthy => 1.0,
            HealthCheckStatus::Degraded => 0.5,
            HealthCheckStatus::Unhealthy => 0.0,
        };
    }
}
