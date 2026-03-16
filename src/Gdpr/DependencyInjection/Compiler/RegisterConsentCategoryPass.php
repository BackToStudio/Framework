<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Gdpr\ConsentCategoryRegistry;

class RegisterConsentCategoryPass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return ConsentCategoryRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.consent_category';
    }
}
