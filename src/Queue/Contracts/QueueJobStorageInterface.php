<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

use BackTo\Framework\Queue\Entity\Job;

/**
 * Core job storage operations: enqueue, claim, lifecycle transitions.
 */
interface QueueJobStorageInterface
{
    /**
     * Insert a new job into the queue.
     */
    public function enqueue(Job $job): int;

    /**
     * Claim the next pending job for a given group.
     *
     * Atomically marks a pending job as running to prevent
     * concurrent workers from processing the same job.
     */
    public function claimNextPending(string $group = 'default'): ?Job;

    /**
     * Mark a job as completed.
     */
    public function markCompleted(int $jobId): void;

    /**
     * Mark a job as failed and increment attempts.
     */
    public function markFailed(int $jobId, string $errorMessage): void;

    /**
     * Release a failed job back to pending status for retry.
     */
    public function release(int $jobId): void;

    /**
     * Find a job by its ID.
     */
    public function find(int $jobId): ?Job;

    /**
     * Cancel a pending job.
     */
    public function cancel(int $jobId): void;

    /**
     * Check if a pending job with the given key and payload hash exists.
     *
     * @param string $jobKey The job type key.
     * @param string $payloadHash MD5 hash of the JSON-encoded payload.
     */
    public function hasPendingDuplicate(string $jobKey, string $payloadHash): bool;
}
