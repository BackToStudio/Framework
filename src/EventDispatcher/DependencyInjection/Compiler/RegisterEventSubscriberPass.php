<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\DependencyInjection\Compiler;

use BackTo\Framework\EventDispatcher\EventDispatcher;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all services tagged 'backto.event_subscriber' and registers
 * them with the EventDispatcher via addSubscriber().
 *
 * This cannot extend AbstractTaggedServiceCompilerPass because it calls
 * addSubscriber() on the EventDispatcher, not on a separate registry.
 */
final class RegisterEventSubscriberPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(EventDispatcher::class)) {
            return;
        }

        $dispatcherDefinition = $container->findDefinition(EventDispatcher::class);

        foreach ($container->findTaggedServiceIds('backto.event_subscriber') as $id => $tags) {
            $dispatcherDefinition->addMethodCall('addSubscriber', [new Reference($id)]);
        }
    }
}
