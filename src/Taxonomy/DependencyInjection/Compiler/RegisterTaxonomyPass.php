<?php

namespace Fantassin\Core\WordPress\Taxonomy\DependencyInjection\Compiler;

use Fantassin\Core\WordPress\Taxonomy\TaxonomyRegistry;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Reference;

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
