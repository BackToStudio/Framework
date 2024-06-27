<?php

namespace BackTo\Framework\Blocks\DependencyInjection\Compiler;

use BackTo\Framework\Blocks\BlockRegistry;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

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
