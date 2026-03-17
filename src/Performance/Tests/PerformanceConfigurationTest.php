<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Performance\PerformanceConfiguration;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class PerformanceConfigurationTest extends TestCase
{
    public function testGetDefaultsReturnsArray(): void
    {
        $defaults = PerformanceConfiguration::getDefaults();

        $this->assertIsArray($defaults);
        $this->assertNotEmpty($defaults);
    }

    public function testAllKeysUsePerformancePrefix(): void
    {
        $defaults = PerformanceConfiguration::getDefaults();

        foreach (array_keys($defaults) as $key) {
            $this->assertStringStartsWith('performance.', $key, "Key '{$key}' must start with 'performance.'");
        }
    }

    public function testNoFrameworkPrefix(): void
    {
        $defaults = PerformanceConfiguration::getDefaults();

        foreach (array_keys($defaults) as $key) {
            $this->assertStringStartsNotWith('framework.', $key, "Key '{$key}' must not start with 'framework.'");
        }
    }

    public function testApplySetsDefaultParameters(): void
    {
        $container = new ContainerBuilder();

        PerformanceConfiguration::apply($container);

        $this->assertTrue($container->getParameter('performance.clean_head'));
        $this->assertTrue($container->getParameter('performance.disable_emojis'));
        $this->assertFalse($container->getParameter('performance.page_cache.enabled'));
        $this->assertSame(3600, $container->getParameter('performance.page_cache.ttl'));
        $this->assertTrue($container->getParameter('performance.htaccess.gzip'));
        $this->assertSame(31536000, $container->getParameter('performance.htaccess.static_ttl'));
    }

    public function testApplyDoesNotOverrideExistingParameters(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('performance.page_cache.ttl', 7200);

        PerformanceConfiguration::apply($container);

        $this->assertSame(7200, $container->getParameter('performance.page_cache.ttl'));
    }

    public function testContainsExpectedSections(): void
    {
        $defaults = PerformanceConfiguration::getDefaults();

        // Head cleanup
        $this->assertArrayHasKey('performance.clean_head', $defaults);
        $this->assertArrayHasKey('performance.disable_emojis', $defaults);

        // Heartbeat
        $this->assertArrayHasKey('performance.heartbeat.disable_frontend', $defaults);

        // Assets
        $this->assertArrayHasKey('performance.defer_scripts', $defaults);
        $this->assertArrayHasKey('performance.defer_exclude', $defaults);

        // Images
        $this->assertArrayHasKey('performance.lazy_load_skip_first', $defaults);

        // HTML minification
        $this->assertArrayHasKey('performance.minify_html', $defaults);

        // Resource hints
        $this->assertArrayHasKey('performance.resource_hints.preconnect', $defaults);

        // Page cache
        $this->assertArrayHasKey('performance.page_cache.enabled', $defaults);

        // Cache preloading
        $this->assertArrayHasKey('performance.cache_preload.enabled', $defaults);

        // .htaccess
        $this->assertArrayHasKey('performance.htaccess.gzip', $defaults);
        $this->assertArrayHasKey('performance.htaccess.browser_cache', $defaults);
        $this->assertArrayHasKey('performance.htaccess.remove_etags', $defaults);
        $this->assertArrayHasKey('performance.htaccess.keep_alive', $defaults);
        $this->assertArrayHasKey('performance.htaccess.static_ttl', $defaults);
    }
}
