<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Security\SecurityRuleRegistry;

final class RegisterSecurityRulePass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return SecurityRuleRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.security_rule';
    }
}
