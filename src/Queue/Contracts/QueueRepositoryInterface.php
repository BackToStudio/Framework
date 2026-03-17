<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;

/**
 * Port interface for queue storage.
 *
 * Abstracts the persistence layer for queued jobs.
 */
interface QueueRepositoryInterface
{
    /**
     * Create the storage table if it does not exist.
     */
    public function createTable(): void;

    /**
     * Drop the storage table.
     */
    public function dropTable(): void;

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
     * Find jobs by status.
     *
     * @return Job[]
     */
    public function findByStatus(JobStatus $status, int $limit = 20, int $offset = 0): array;

    /**
     * Count jobs by status.
     */
    public function countByStatus(JobStatus $status): int;

    /**
     * Delete completed jobs older than the given number of seconds.
     */
    public function cleanup(int $olderThanSeconds = 86400): int;

    /**
     * Delete failed jobs (retries exhausted) older than the given number of seconds.
     */
    public function cleanupFailed(int $olderThanSeconds = 604800): int;

    /**
     * Cancel a pending job.
     */
    public function cancel(int $jobId): void;

    /**
     * Rescue stuck running jobs older than the given timeout in seconds.
     *
     * Jobs that have been running longer than the timeout are
     * considered stuck and will be released back to pending.
     */
    public function rescueStuck(int $timeoutSeconds = 300): int;

    /**
     * Check if a pending job with the given key and payload hash exists.
     *
     * @param string $jobKey The job type key.
     * @param string $payloadHash MD5 hash of the JSON-encoded payload.
     */
    public function hasPendingDuplicate(string $jobKey, string $payloadHash): bool;

    /**
     * Get all distinct group names that have pending or running jobs.
     *
     * @return string[]
     */
    public function getActiveGroups(): array;
}
