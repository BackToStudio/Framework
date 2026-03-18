<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

use BackTo\Framework\Queue\Entity\Job;
use BackTo\Framework\Queue\Entity\JobStatus;

/**
 * Read-only query operations for inspecting the queue.
 */
interface QueueQueryInterface
{
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
     * Get all distinct group names that have pending or running jobs.
     *
     * @return string[]
     */
    public function getActiveGroups(): array;
}
