<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

/**
 * Port interface for an auto-remediation action.
 *
 * Implementations provide a corrective action for a specific health check.
 * When the associated check reports degraded or unhealthy status,
 * the remediation handler attempts to fix the issue automatically.
 */
interface RemediationInterface
{
    /**
     * Name of the health check this remediation handles (e.g., 'cache', 'queue').
     */
    public function getTargetCheck(): string;

    /**
     * Attempt to fix the issue.
     *
     * @param array<string, mixed> $metadata Health check metadata for context
     * @return bool True if remediation was attempted successfully
     */
    public function remediate(array $metadata = []): bool;

    /**
     * Human-readable description of the remediation action.
     */
    public function getDescription(): string;
}
