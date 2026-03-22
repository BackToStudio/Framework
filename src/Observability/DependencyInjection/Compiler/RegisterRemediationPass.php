<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Observability\AutoRemediation;

final class RegisterRemediationPass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return AutoRemediation::class;
    }

    protected function getTag(): string
    {
        return 'observability.remediation';
    }

    protected function getRegistryMethod(): string
    {
        return 'addHandler';
    }
}
