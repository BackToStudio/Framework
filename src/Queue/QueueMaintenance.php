<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue;

use BackTo\Framework\Queue\Contracts\QueueMaintenanceInterface;

/**
 * Handles queue maintenance tasks: rescuing stuck jobs and cleaning up old ones.
 */
class QueueMaintenance
{
    private const STUCK_TIMEOUT = 300;
    private const COMPLETED_TTL = 86400;
    private const FAILED_TTL = 604800;

    private readonly QueueMaintenanceInterface $repository;

    public function __construct(QueueMaintenanceInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Rescue jobs stuck in running state for longer than the timeout.
     */
    public function rescueStuckJobs(): void
    {
        $this->repository->rescueStuck(self::STUCK_TIMEOUT);
    }

    /**
     * Clean up completed jobs (>24h) and exhausted failed jobs (>7 days).
     */
    public function cleanupJobs(): void
    {
        $this->repository->cleanup(self::COMPLETED_TTL);
        $this->repository->cleanupFailed(self::FAILED_TTL);
    }
}
