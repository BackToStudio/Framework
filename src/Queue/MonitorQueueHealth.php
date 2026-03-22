<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Queue\Contracts\QueueQueryInterface;
use BackTo\Framework\Queue\Entity\JobStatus;

/**
 * Health check and metric recorder for the queue system.
 *
 * Reports queue depth, failed job count, and stuck job indicators.
 * Alerts when the queue is in a degraded state.
 */
final class MonitorQueueHealth implements HealthCheckInterface
{
    private const WARN_PENDING_THRESHOLD = 100;
    private const WARN_FAILED_THRESHOLD = 10;

    private readonly QueueQueryInterface $query;
    private readonly MetricStoreInterface $metricStore;
    private readonly AlertDispatcherInterface $alertDispatcher;

    public function __construct(
        QueueQueryInterface $query,
        MetricStoreInterface $metricStore,
        AlertDispatcherInterface $alertDispatcher,
    ) {
        $this->query = $query;
        $this->metricStore = $metricStore;
        $this->alertDispatcher = $alertDispatcher;
    }

    public function getName(): string
    {
        return 'queue';
    }

    public function check(): HealthCheckResult
    {
        $pending = $this->query->countByStatus(JobStatus::Pending);
        $running = $this->query->countByStatus(JobStatus::Running);
        $failed = $this->query->countByStatus(JobStatus::Failed);
        $groups = $this->query->getActiveGroups();

        // Record metrics
        $this->metricStore->record('queue.pending', (float) $pending, 'gauge');
        $this->metricStore->record('queue.running', (float) $running, 'gauge');
        $this->metricStore->record('queue.failed', (float) $failed, 'gauge');
        $this->metricStore->record('queue.active_groups', (float) \count($groups), 'gauge');

        $metadata = [
            'pending' => $pending,
            'running' => $running,
            'failed' => $failed,
            'active_groups' => $groups,
        ];

        if ($failed >= self::WARN_FAILED_THRESHOLD) {
            $this->alertDispatcher->warning(
                \sprintf('Queue has %d failed jobs', $failed),
                $metadata,
            );

            return HealthCheckResult::degraded(
                \sprintf('%d failed jobs detected', $failed),
                $metadata,
            );
        }

        if ($pending >= self::WARN_PENDING_THRESHOLD) {
            $this->alertDispatcher->warning(
                \sprintf('Queue backlog: %d pending jobs', $pending),
                $metadata,
            );

            return HealthCheckResult::degraded(
                \sprintf('High queue depth: %d pending jobs', $pending),
                $metadata,
            );
        }

        return HealthCheckResult::healthy(
            \sprintf('%d pending, %d running, %d failed', $pending, $running, $failed),
            $metadata,
        );
    }
}
