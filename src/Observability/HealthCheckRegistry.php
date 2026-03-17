<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Observability\Contracts\HealthCheckInterface;
use BackTo\Framework\Observability\Contracts\HealthCheckResult;

/**
 * Collects and executes all registered health checks.
 */
final class HealthCheckRegistry implements RegistryInterface
{
    /** @var HealthCheckInterface[] */
    private array $checks = [];

    public function add(HealthCheckInterface $check): self
    {
        $this->checks[] = $check;

        return $this;
    }

    /**
     * Run all health checks and return aggregated results.
     *
     * @return array<string, HealthCheckResult>
     */
    public function runAll(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            try {
                $results[$check->getName()] = $check->check();
            } catch (\Throwable $e) {
                $results[$check->getName()] = HealthCheckResult::unhealthy($e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Check if all services are healthy.
     */
    public function isHealthy(): bool
    {
        foreach ($this->runAll() as $result) {
            if (!$result->isHealthy()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array{status: string, message: string, metadata: array<string, mixed>}>
     */
    public function toArray(): array
    {
        $output = [];

        foreach ($this->runAll() as $name => $result) {
            $output[$name] = $result->toArray();
        }

        return $output;
    }
}
