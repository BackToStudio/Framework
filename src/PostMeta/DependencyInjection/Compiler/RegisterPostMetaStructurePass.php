<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\PostMeta\PostMetaStructureRegistry;

class RegisterPostMetaStructurePass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return PostMetaStructureRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.post_meta';
    }
}
