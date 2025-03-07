<?php

namespace BackTo\Framework\PostType\DependencyInjection\Compiler;

use BackTo\Framework\PostType\PostTypeRegistry;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

/**
 * Register all Custom Post Types that have the "wordpress.post_type" tag into the container.
 */
class RegisterPostTypePass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        if ( ! $container->hasDefinition(PostTypeRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(PostTypeRegistry::class);

        foreach ($container->findTaggedServiceIds('wordpress.post_type') as $id => $tags) {
            $registryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
