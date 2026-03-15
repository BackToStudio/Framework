<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Hooks\HookRegistry;

class RegisterHookPass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return HookRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.hook';
    }

    protected function getRegistryMethod(): string
    {
        return 'addHook';
    }
}
