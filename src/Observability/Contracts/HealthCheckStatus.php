<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Contracts;

enum HealthCheckStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
}
