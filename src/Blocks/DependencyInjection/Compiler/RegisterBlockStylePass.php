<?php

namespace BackTo\Framework\Blocks\DependencyInjection\Compiler;

use BackTo\Framework\Blocks\BlockStyleRegistry;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

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
