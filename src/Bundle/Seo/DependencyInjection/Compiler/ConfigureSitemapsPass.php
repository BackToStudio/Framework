<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\DependencyInjection\Compiler;

use BackTo\Framework\Bundle\Seo\Hooks\ConfigureSitemaps;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reads seo.sitemap_* parameters and wires them into
 * the ConfigureSitemaps service via setter calls.
 */
final class ConfigureSitemapsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ConfigureSitemaps::class)) {
            return;
        }

        $definition = $container->getDefinition(ConfigureSitemaps::class);

        $mapping = [
            'seo.sitemap_enabled' => 'setEnabled',
            'seo.sitemap_users_enabled' => 'setUsersEnabled',
            'seo.sitemap_excluded_post_types' => 'setExcludedPostTypes',
            'seo.sitemap_excluded_taxonomies' => 'setExcludedTaxonomies',
            'seo.sitemap_excluded_post_ids' => 'setExcludedPostIds',
            'seo.sitemap_excluded_term_ids' => 'setExcludedTermIds',
            'seo.sitemap_max_urls' => 'setMaxUrls',
        ];

        foreach ($mapping as $parameter => $method) {
            if ($container->hasParameter($parameter)) {
                $definition->addMethodCall($method, [$container->getParameter($parameter)]);
            }
        }
    }
}
