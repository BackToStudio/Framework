<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Taxonomy\TaxonomyRegistry;

class RegisterTaxonomyPass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return TaxonomyRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.taxonomy';
    }
}
