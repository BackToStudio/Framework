<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

use BackTo\Framework\Queue\Entity\Job;

/**
 * Port interface for dispatching jobs to the queue.
 */
interface QueueDispatcherInterface
{
    /**
     * Dispatch a job for async processing.
     *
     * @param string $jobKey The job type key (matching JobInterface::getKey()).
     * @param array<string, mixed> $payload Data to pass to the job handler.
     * @param int $delay Number of seconds to delay before the job becomes available.
     * @param string $group The queue group for sequential processing.
     *
     * @return int The enqueued job ID.
     */
    public function dispatch(string $jobKey, array $payload = [], int $delay = 0, string $group = 'default'): int;

    /**
     * Dispatch a unique job — skips if an identical pending job already exists.
     *
     * @param string $jobKey The job type key.
     * @param array<string, mixed> $payload Data to pass to the job handler.
     * @param int $delay Number of seconds to delay.
     * @param string $group The queue group.
     *
     * @return int|null The job ID, or null if a duplicate was skipped.
     */
    public function dispatchUnique(string $jobKey, array $payload = [], int $delay = 0, string $group = 'default'): ?int;

    /**
     * Schedule a recurring job.
     *
     * @param string $jobKey The job type key.
     * @param int $intervalSeconds The interval between recurrences.
     * @param array<string, mixed> $payload Data to pass to the job handler.
     * @param string $group The queue group.
     *
     * @return int The enqueued job ID.
     */
    public function schedule(string $jobKey, int $intervalSeconds, array $payload = [], string $group = 'default'): int;
}
