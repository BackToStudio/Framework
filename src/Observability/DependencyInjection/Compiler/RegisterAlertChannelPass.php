<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Observability\Alert\AlertDispatcher;

final class RegisterAlertChannelPass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return AlertDispatcher::class;
    }

    protected function getTag(): string
    {
        return 'observability.alert_channel';
    }

    protected function getRegistryMethod(): string
    {
        return 'addChannel';
    }
}
