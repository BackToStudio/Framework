<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks\DependencyInjection\Compiler;

use BackTo\Framework\Bundle\Blocks\BlockRegistry;
use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;

final class RegisterBlockPass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return BlockRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.block';
    }
}
