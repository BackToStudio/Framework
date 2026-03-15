<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\RestApi\RestRouteRegistry;

class RegisterRestRoutePass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return RestRouteRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.rest_route';
    }
}
