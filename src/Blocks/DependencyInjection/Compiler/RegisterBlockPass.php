<?php

namespace Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler;

use Fantassin\Core\WordPress\Blocks\BlockRegistry;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Reference;

class RegisterBlockPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(BlockRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(BlockRegistry::class);

        foreach ($container->findTaggedServiceIds('wordpress.block') as $id => $tags) {
            $registryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
