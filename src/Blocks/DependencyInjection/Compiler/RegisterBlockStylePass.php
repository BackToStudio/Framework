<?php

namespace Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler;

use Fantassin\Core\WordPress\Blocks\BlockStyleRegistry;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Reference;

class RegisterBlockStylePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(BlockStyleRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(BlockStyleRegistry::class);

        foreach ($container->findTaggedServiceIds('wordpress.block_style') as $id => $tags) {
            $registryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
