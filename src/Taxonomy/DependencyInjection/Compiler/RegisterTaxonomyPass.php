<?php

namespace BackTo\Framework\Taxonomy\DependencyInjection\Compiler;

use BackTo\Framework\Taxonomy\TaxonomyRegistry;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

/**
 * Register all Custom Taxonomies that have the "wordpress.taxonomy" tag into the container.
 */
class RegisterTaxonomyPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(TaxonomyRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(TaxonomyRegistry::class);

        foreach ($container->findTaggedServiceIds('wordpress.taxonomy') as $id => $tags) {
            $registryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
