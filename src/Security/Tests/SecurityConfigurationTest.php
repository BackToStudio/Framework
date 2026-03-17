<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Security\SecurityConfiguration;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class SecurityConfigurationTest extends TestCase
{
    public function testGetDefaultsReturnsArray(): void
    {
        $defaults = SecurityConfiguration::getDefaults();

        $this->assertIsArray($defaults);
        $this->assertNotEmpty($defaults);
    }

    public function testAllKeysUseSecurityPrefix(): void
    {
        $defaults = SecurityConfiguration::getDefaults();

        foreach (array_keys($defaults) as $key) {
            $this->assertStringStartsWith('security.', $key, "Key '{$key}' must start with 'security.'");
        }
    }

    public function testNoFrameworkPrefix(): void
    {
        $defaults = SecurityConfiguration::getDefaults();

        foreach (array_keys($defaults) as $key) {
            $this->assertStringStartsNotWith('framework.', $key, "Key '{$key}' must not start with 'framework.'");
        }
    }

    public function testApplySetsDefaultParameters(): void
    {
        $container = new ContainerBuilder();

        SecurityConfiguration::apply($container);

        $this->assertTrue($container->getParameter('security.headers_enabled'));
        $this->assertTrue($container->getParameter('security.xmlrpc_disabled'));
        $this->assertTrue($container->getParameter('security.hide_version'));
        $this->assertFalse($container->getParameter('security.csp_report_only'));
        $this->assertSame(12, $container->getParameter('security.password_min_length'));
        $this->assertSame(1, $container->getParameter('security.max_concurrent_sessions'));
        $this->assertTrue($container->getParameter('security.rest_api_require_auth'));
        $this->assertTrue($container->getParameter('security.disable_file_editor'));
        $this->assertFalse($container->getParameter('security.two_factor_enabled'));
        $this->assertSame('WordPress', $container->getParameter('security.two_factor_issuer'));
    }

    public function testApplyDoesNotOverrideExistingParameters(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('security.password_min_length', 16);

        SecurityConfiguration::apply($container);

        $this->assertSame(16, $container->getParameter('security.password_min_length'));
    }

    public function testContainsAllExpectedKeys(): void
    {
        $defaults = SecurityConfiguration::getDefaults();

        $this->assertArrayHasKey('security.headers_enabled', $defaults);
        $this->assertArrayHasKey('security.xmlrpc_disabled', $defaults);
        $this->assertArrayHasKey('security.hide_version', $defaults);
        $this->assertArrayHasKey('security.csp_report_only', $defaults);
        $this->assertArrayHasKey('security.password_min_length', $defaults);
        $this->assertArrayHasKey('security.max_concurrent_sessions', $defaults);
        $this->assertArrayHasKey('security.rest_api_require_auth', $defaults);
        $this->assertArrayHasKey('security.disable_file_editor', $defaults);
        $this->assertArrayHasKey('security.two_factor_enabled', $defaults);
        $this->assertArrayHasKey('security.two_factor_issuer', $defaults);
    }
}
