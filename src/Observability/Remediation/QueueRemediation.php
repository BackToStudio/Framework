<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Remediation;

use BackTo\Framework\Observability\Contracts\RemediationInterface;
use BackTo\Framework\Queue\QueueMaintenance;

/**
 * Auto-remediation for queue health check failures.
 *
 * When the queue reports degraded status (stuck or failed jobs),
 * this handler rescues stuck jobs and cleans up old ones.
 */
final class QueueRemediation implements RemediationInterface
{
    private readonly QueueMaintenance $maintenance;

    public function __construct(QueueMaintenance $maintenance)
    {
        $this->maintenance = $maintenance;
    }

    public function getTargetCheck(): string
    {
        return 'queue';
    }

    public function remediate(array $metadata = []): bool
    {
        $this->maintenance->rescueStuckJobs();
        $this->maintenance->cleanupJobs();

        return true;
    }

    public function getDescription(): string
    {
        return 'Rescue stuck jobs and clean up old completed/failed jobs';
    }
}
