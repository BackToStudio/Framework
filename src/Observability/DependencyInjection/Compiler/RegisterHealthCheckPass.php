<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Observability\HealthCheckRegistry;

final class RegisterHealthCheckPass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return HealthCheckRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.health_check';
    }

    protected function getRegistryMethod(): string
    {
        return 'add';
    }
}
