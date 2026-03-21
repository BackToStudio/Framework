<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Bundle\Seo\DependencyInjection\Compiler\ConfigureSitemapsPass;
use BackTo\Framework\Bundle\Seo\Hooks\ConfigureSitemaps;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Definition;
use PHPUnit\Framework\TestCase;

class ConfigureSitemapsPassTest extends TestCase
{
    private ContainerBuilder $container;
    private ConfigureSitemapsPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new ConfigureSitemapsPass();
    }

    public function testSkipsWhenServiceNotRegistered(): void
    {
        $this->pass->process($this->container);

        $this->assertFalse($this->container->hasDefinition(ConfigureSitemaps::class));
    }

    public function testConfiguresAllParameters(): void
    {
        $definition = new Definition(ConfigureSitemaps::class);
        $this->container->setDefinition(ConfigureSitemaps::class, $definition);

        $this->container->setParameter('seo.sitemap_enabled', false);
        $this->container->setParameter('seo.sitemap_users_enabled', false);
        $this->container->setParameter('seo.sitemap_excluded_post_types', ['attachment']);
        $this->container->setParameter('seo.sitemap_excluded_taxonomies', ['post_tag']);
        $this->container->setParameter('seo.sitemap_excluded_post_ids', [10, 20]);
        $this->container->setParameter('seo.sitemap_excluded_term_ids', [5]);
        $this->container->setParameter('seo.sitemap_max_urls', 1000);

        $this->pass->process($this->container);

        $calls = $definition->getMethodCalls();
        $methods = \array_column($calls, 0);

        $this->assertContains('setEnabled', $methods);
        $this->assertContains('setUsersEnabled', $methods);
        $this->assertContains('setExcludedPostTypes', $methods);
        $this->assertContains('setExcludedTaxonomies', $methods);
        $this->assertContains('setExcludedPostIds', $methods);
        $this->assertContains('setExcludedTermIds', $methods);
        $this->assertContains('setMaxUrls', $methods);
    }

    public function testSkipsMissingParameters(): void
    {
        $definition = new Definition(ConfigureSitemaps::class);
        $this->container->setDefinition(ConfigureSitemaps::class, $definition);

        $this->container->setParameter('seo.sitemap_enabled', true);

        $this->pass->process($this->container);

        $calls = $definition->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertSame('setEnabled', $calls[0][0]);
    }
}
