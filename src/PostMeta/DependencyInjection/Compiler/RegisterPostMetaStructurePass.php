<?php

namespace BackTo\Framework\PostMeta\DependencyInjection\Compiler;

use BackTo\Framework\PostMeta\RegisterPostMetaStructure;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

/**
 * Register all Custom Post Meta that have the "wordpress.post_meta" tag into the container.
 */
class RegisterPostMetaStructurePass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(RegisterPostMetaStructure::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(RegisterPostMetaStructure::class);

        foreach ($container->findTaggedServiceIds('wordpress.post_meta') as $id => $tags) {
            $registryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
