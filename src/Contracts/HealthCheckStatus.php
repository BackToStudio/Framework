<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

enum HealthCheckStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
}
