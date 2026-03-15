<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\Tests;

use BackTo\Framework\Compose\Configuration\FrameworkConfiguration;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class FrameworkConfigurationTest extends TestCase
{
    public function testApplySetsDefaultParameters(): void
    {
        $container = new ContainerBuilder();

        FrameworkConfiguration::apply($container);

        $this->assertSame(3600, $container->getParameter('framework.cache.ttl'));
        $this->assertTrue($container->getParameter('framework.cache.enabled'));
        $this->assertSame('|', $container->getParameter('framework.seo.title_separator'));
        $this->assertSame('app/v1', $container->getParameter('framework.rest_api.default_namespace'));
    }

    public function testApplyDoesNotOverrideExistingParameters(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('framework.cache.ttl', 7200);

        FrameworkConfiguration::apply($container);

        $this->assertSame(7200, $container->getParameter('framework.cache.ttl'));
    }

    public function testGetDefaultsReturnsArray(): void
    {
        $defaults = FrameworkConfiguration::getDefaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('framework.cache.ttl', $defaults);
        $this->assertArrayHasKey('framework.seo.title_separator', $defaults);
        $this->assertArrayHasKey('framework.rest_api.default_namespace', $defaults);
        $this->assertArrayHasKey('framework.assets.version_strategy', $defaults);
    }
}
