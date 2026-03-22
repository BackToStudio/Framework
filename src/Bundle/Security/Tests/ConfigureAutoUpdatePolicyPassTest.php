<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Bundle\Security\DependencyInjection\Compiler\ConfigureAutoUpdatePolicyPass;
use BackTo\Framework\Bundle\Security\Hardening\AutoUpdatePolicy;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Definition;
use PHPUnit\Framework\TestCase;

class ConfigureAutoUpdatePolicyPassTest extends TestCase
{
    private ContainerBuilder $container;
    private ConfigureAutoUpdatePolicyPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new ConfigureAutoUpdatePolicyPass();
    }

    public function testSkipsWhenAutoUpdatePolicyNotRegistered(): void
    {
        $this->pass->process($this->container);

        $this->assertFalse($this->container->hasDefinition(AutoUpdatePolicy::class));
    }

    public function testConfiguresBooleanParameters(): void
    {
        $definition = new Definition(AutoUpdatePolicy::class);
        $this->container->setDefinition(AutoUpdatePolicy::class, $definition);

        $this->container->setParameter('security.auto_update_major_core', true);
        $this->container->setParameter('security.auto_update_minor_core', false);
        $this->container->setParameter('security.auto_update_plugins', true);
        $this->container->setParameter('security.auto_update_themes', true);
        $this->container->setParameter('security.auto_update_translations', false);

        $this->pass->process($this->container);

        $calls = $definition->getMethodCalls();
        $methods = \array_column($calls, 0);

        $this->assertContains('setMajorCore', $methods);
        $this->assertContains('setMinorCore', $methods);
        $this->assertContains('setPlugins', $methods);
        $this->assertContains('setThemes', $methods);
        $this->assertContains('setTranslations', $methods);
    }

    public function testConfiguresAllowedPluginsAndThemes(): void
    {
        $definition = new Definition(AutoUpdatePolicy::class);
        $this->container->setDefinition(AutoUpdatePolicy::class, $definition);

        $this->container->setParameter('security.auto_update_allowed_plugins', ['akismet/akismet.php']);
        $this->container->setParameter('security.auto_update_allowed_themes', ['twentytwentyfive']);

        $this->pass->process($this->container);

        $calls = $definition->getMethodCalls();
        $callMap = [];

        foreach ($calls as [$method, $args]) {
            $callMap[$method] = $args;
        }

        $this->assertSame(['akismet/akismet.php'], $callMap['setAllowedPlugins'][0]);
        $this->assertSame(['twentytwentyfive'], $callMap['setAllowedThemes'][0]);
    }

    public function testSkipsMissingParameters(): void
    {
        $definition = new Definition(AutoUpdatePolicy::class);
        $this->container->setDefinition(AutoUpdatePolicy::class, $definition);

        $this->container->setParameter('security.auto_update_major_core', true);

        $this->pass->process($this->container);

        $calls = $definition->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertSame('setMajorCore', $calls[0][0]);
        $this->assertTrue($calls[0][1][0]);
    }
}
