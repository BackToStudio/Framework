<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;

/**
 * Sends a daily operational digest via the alert system.
 *
 * Collects health check results, metric summaries, and queue status
 * into a single report dispatched as an info-level alert every 24 hours.
 */
final class ScheduleDailyDigest implements Hooks
{
    private const CRON_HOOK = 'backto_daily_digest';
    private const SCHEDULE = 'daily';
    private const SUMMARY_WINDOW = 86400; // 24 hours

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
        $this->hookDispatcher->addAction('admin_init', [$this, 'ensureScheduled']);
        $this->hookDispatcher->addAction(self::CRON_HOOK, [$this, 'sendDigest']);
    }

    public function ensureScheduled(): void
    {
        if (!$this->cronScheduler->isScheduled(self::CRON_HOOK)) {
            $this->cronScheduler->scheduleRecurring(self::CRON_HOOK, self::SCHEDULE);
        }
    }

    public function sendDigest(): void
    {
        $healthResults = $this->registry->runAll();
        $summary = $this->metricStore->summary(\time() - self::SUMMARY_WINDOW);

        $healthy = 0;
        $degraded = 0;
        $unhealthy = 0;
        $details = [];

        foreach ($healthResults as $name => $result) {
            match ($result->getStatus()) {
                'healthy' => $healthy++,
                'degraded' => $degraded++,
                'unhealthy' => $unhealthy++,
                default => null,
            };
            $details[$name] = $result->getStatus() . ': ' . $result->getMessage();
        }

        $level = $unhealthy > 0 ? 'critical' : ($degraded > 0 ? 'warning' : 'info');

        $this->alertDispatcher->dispatch($level, 'Daily operations digest', [
            'health_summary' => \sprintf('%d healthy, %d degraded, %d unhealthy', $healthy, $degraded, $unhealthy),
            'health_details' => $details,
            'metrics_tracked' => \count($summary),
            'metric_highlights' => $this->extractHighlights($summary),
        ]);

        $this->metricStore->record('digest.sent', 1.0, 'counter');
    }

    /**
     * @param array<string, array{current: float, min: float, max: float, avg: float, count: int}> $summary
     * @return array<string, string>
     */
    private function extractHighlights(array $summary): array
    {
        $highlights = [];

        foreach ($summary as $name => $data) {
            $highlights[$name] = \sprintf(
                'current=%.2f avg=%.2f min=%.2f max=%.2f (%d samples)',
                $data['current'],
                $data['avg'],
                $data['min'],
                $data['max'],
                $data['count'],
            );
        }

        return $highlights;
    }
}
