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

        $this->assertSame('app/v1', $container->getParameter('framework.rest_api.default_namespace'));
        $this->assertSame(10, $container->getParameter('framework.rest_api.default_per_page'));
        $this->assertSame('file', $container->getParameter('framework.assets.version_strategy'));
        $this->assertSame('error', $container->getParameter('framework.observability.log_level'));
    }

    public function testApplyDoesNotOverrideExistingParameters(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('framework.rest_api.default_per_page', 25);

        FrameworkConfiguration::apply($container);

        $this->assertSame(25, $container->getParameter('framework.rest_api.default_per_page'));
    }

    public function testGetDefaultsReturnsArray(): void
    {
        $defaults = FrameworkConfiguration::getDefaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('framework.rest_api.default_namespace', $defaults);
        $this->assertArrayHasKey('framework.assets.version_strategy', $defaults);
        $this->assertArrayHasKey('framework.observability.log_level', $defaults);
        $this->assertArrayNotHasKey('cache.ttl', $defaults);
        $this->assertArrayNotHasKey('seo.title_separator', $defaults);
    }
}
