<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\PostType\PostTypeRegistry;

final class RegisterPostTypePass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return PostTypeRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.post_type';
    }
}
