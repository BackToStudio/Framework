<?php

declare(strict_types=1);

namespace BackTo\Framework\Gdpr\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Gdpr\TrackingScriptRegistry;

class RegisterTrackingScriptPass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return TrackingScriptRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.tracking_script';
    }
}
