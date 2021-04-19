<?php

namespace Fantassin\Core\WordPress\Taxonomy\DependencyInjection\Compiler;

use Fantassin\Core\WordPress\Taxonomy\TaxonomyRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register all Custom Post Type that have the "wordpress.post_type" tag into the container.
 */
class RegisterTaxonomyPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {

        if ( ! $container->hasDefinition(TaxonomyRegistry::class)) {
            return;
        }

        $registryDefinition = $container->findDefinition(TaxonomyRegistry::class);

        foreach ($container->findTaggedServiceIds('wordpress.taxonomy') as $id => $tags) {
            $registryDefinition->addMethodCall('add', [new Reference($id)]);
        }
    }
}
