<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks\DependencyInjection\Compiler;

use BackTo\Framework\Bundle\Blocks\BlockStyleRegistry;
use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;

final class RegisterBlockStylePass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return BlockStyleRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.block_style';
    }
}
