<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Port interface for health checks.
 *
 * Implementations verify that a specific service or subsystem is operational.
 */
interface HealthCheckInterface
{
    /**
     * Human-readable name of the component being checked.
     */
    public function getName(): string;

    /**
     * Run the health check.
     *
     * @return HealthCheckResult
     */
    public function check(): HealthCheckResult;
}
