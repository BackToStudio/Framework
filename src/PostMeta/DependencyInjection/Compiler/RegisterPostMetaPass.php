<?php

namespace BackTo\Framework\PostMeta\DependencyInjection\Compiler;

use BackTo\Framework\Taxonomy\TaxonomyRegistry;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

/**
 * Register all Custom Taxonomies that have the "wordpress.post_meta" tag into the container.
 */
class RegisterPostMetaPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(TaxonomyRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(TaxonomyRegistry::class);

        foreach ($container->findTaggedServiceIds('wordpress.post_meta') as $id => $tags) {
            $registryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
