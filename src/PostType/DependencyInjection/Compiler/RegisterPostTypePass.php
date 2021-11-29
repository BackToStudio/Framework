<?php

namespace Fantassin\Core\WordPress\PostType\DependencyInjection\Compiler;

use Fantassin\Core\WordPress\PostType\PostTypeRegistry;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Reference;

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
