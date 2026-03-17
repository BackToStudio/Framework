<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\DependencyInjection\Compiler;

use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;
use BackTo\Framework\Queue\QueueRegistry;

final class RegisterQueuePass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return QueueRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.queue_job';
    }
}
