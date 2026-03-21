<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Remediation;

use BackTo\Framework\Bundle\Performance\Contracts\DatabaseOptimizerInterface;
use BackTo\Framework\Observability\Contracts\RemediationInterface;

/**
 * Auto-remediation for database health issues.
 *
 * Runs database cleanup (revisions, auto-drafts, expired transients,
 * orphaned metadata) and table optimization.
 */
final class DatabaseRemediation implements RemediationInterface
{
    private readonly DatabaseOptimizerInterface $optimizer;

    public function __construct(DatabaseOptimizerInterface $optimizer)
    {
        $this->optimizer = $optimizer;
    }

    public function getTargetCheck(): string
    {
        return 'database';
    }

    public function remediate(array $metadata = []): bool
    {
        $this->optimizer->cleanup();
        $this->optimizer->optimizeTables();

        return true;
    }

    public function getDescription(): string
    {
        return 'Clean up revisions, auto-drafts, expired transients, and optimize tables';
    }
}
