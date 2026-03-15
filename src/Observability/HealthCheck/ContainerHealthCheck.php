<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\HealthCheck;

use BackTo\Framework\Observability\Contracts\HealthCheckInterface;
use BackTo\Framework\Observability\Contracts\HealthCheckResult;

/**
 * Verifies that the DI container is compiled and functional.
 */
class ContainerHealthCheck implements HealthCheckInterface
{
    private string $buildDir;

    public function __construct(string $buildDir)
    {
        $this->buildDir = $buildDir;
    }

    public function getName(): string
    {
        return 'container';
    }

    public function check(): HealthCheckResult
    {
        $containerFile = \rtrim($this->buildDir, '/') . '/container.php';

        if (!\is_file($containerFile)) {
            return HealthCheckResult::unhealthy('Container not compiled', [
                'path' => $containerFile,
            ]);
        }

        $age = \time() - (int) \filemtime($containerFile);

        if ($age > 86400) {
            return HealthCheckResult::degraded('Container compiled over 24h ago', [
                'age_seconds' => $age,
            ]);
        }

        return HealthCheckResult::healthy('Container compiled', [
            'age_seconds' => $age,
        ]);
    }
}
