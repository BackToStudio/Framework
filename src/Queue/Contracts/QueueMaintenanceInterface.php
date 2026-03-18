<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Contracts;

/**
 * Maintenance operations for cleaning up and rescuing queue jobs.
 */
interface QueueMaintenanceInterface
{
    /**
     * Delete completed jobs older than the given number of seconds.
     */
    public function cleanup(int $olderThanSeconds = 86400): int;

    /**
     * Delete failed jobs (retries exhausted) older than the given number of seconds.
     */
    public function cleanupFailed(int $olderThanSeconds = 604800): int;

    /**
     * Rescue stuck running jobs older than the given timeout in seconds.
     *
     * Jobs that have been running longer than the timeout are
     * considered stuck and will be released back to pending.
     */
    public function rescueStuck(int $timeoutSeconds = 300): int;
}
