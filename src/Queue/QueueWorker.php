<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Queue\Contracts\QueueJobStorageInterface;
use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Factory\JobFactory;

/**
 * Processes jobs from the queue.
 *
 * The worker claims pending jobs, executes their handlers,
 * manages retries, and handles recurring job rescheduling.
 */
final class QueueWorker
{
    private const MAX_BATCH_SIZE = 100;

    private readonly QueueJobStorageInterface $repository;
    private readonly QueueRegistry $registry;
    private readonly JobFactory $factory;

    public function __construct(
        QueueJobStorageInterface $repository,
        QueueRegistry $registry,
        JobFactory $factory
    ) {
        $this->repository = $repository;
        $this->registry = $registry;
        $this->factory = $factory;
    }

    /**
     * Process a batch of jobs from the queue.
     *
     * @param string $group The queue group to process.
     * @param int $batchSize Maximum number of jobs to process in this run (capped at 100).
     *
     * @return int Number of jobs processed.
     */
    public function processQueue(string $group = 'default', int $batchSize = 10): int
    {
        $batchSize = \min(\max($batchSize, 1), self::MAX_BATCH_SIZE);
        $processed = 0;

        for ($i = 0; $i < $batchSize; $i++) {
            $job = $this->repository->claimNextPending($group);

            if ($job === null) {
                break;
            }

            $this->processJob($job);
            $processed++;
        }

        return $processed;
    }

    /**
     * Process a single claimed job.
     */
    private function processJob(Job $job): void
    {
        $handler = $this->registry->get($job->getKey());

        if ($handler === null) {
            $this->repository->markFailed($job->getId(), "No handler registered for job key: {$job->getKey()}");

            return;
        }

        try {
            $handler->handle($job->getPayload());
        } catch (\Throwable $e) {
            $this->handleFailure($job, $e);

            return;
        }

        // For recurring jobs, enqueue the next occurrence BEFORE marking
        // the current job complete. This prevents silent loss of recurring
        // jobs if the reschedule enqueue fails — the current job remains
        // in "running" state and can be recovered.
        if ($job->isRecurring()) {
            $this->rescheduleRecurring($job);
        }

        $this->repository->markCompleted($job->getId());
    }

    /**
     * Handle a job failure with retry logic.
     */
    private function handleFailure(Job $job, \Throwable $exception): void
    {
        $this->repository->markFailed($job->getId(), $exception->getMessage());

        // Refresh to get updated attempts count.
        $updatedJob = $this->repository->find($job->getId());

        if ($updatedJob !== null && $updatedJob->canRetry()) {
            $this->repository->release($job->getId());
        }
    }

    /**
     * Reschedule a recurring job for its next execution.
     */
    private function rescheduleRecurring(Job $job): void
    {
        $nextJob = $this->factory->create(
            $job->getKey(),
            $job->getPayload(),
            $job->getGroup(),
            $job->getMaxRetries(),
            $job->getIntervalSeconds(),
            $job->getIntervalSeconds()
        );

        $this->repository->enqueue($nextJob);
    }
}
