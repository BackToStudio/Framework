<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Security\DependencyInjection\Compiler\RegisterSecurityRulePass;
use BackTo\Framework\Security\SecurityRuleRegistry;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Definition;
use PHPUnit\Framework\TestCase;

class RegisterSecurityRulePassTest extends TestCase
{
    private RegisterSecurityRulePass $pass;

    protected function setUp(): void
    {
        $this->pass = new RegisterSecurityRulePass();
    }

    public function testProcessRegistersTaggedServices(): void
    {
        $container = new ContainerBuilder();

        $registryDefinition = new Definition(SecurityRuleRegistry::class);
        $container->setDefinition(SecurityRuleRegistry::class, $registryDefinition);

        $ruleDefinition = new Definition(\stdClass::class);
        $ruleDefinition->addTag('wordpress.security_rule');
        $container->setDefinition('app.security.test_rule', $ruleDefinition);

        $this->pass->process($container);

        $calls = $registryDefinition->getMethodCalls();
        $this->assertCount(1, $calls);
        $this->assertSame('add', $calls[0][0]);
    }

    public function testProcessSkipsWhenRegistryNotDefined(): void
    {
        $container = new ContainerBuilder();

        $ruleDefinition = new Definition(\stdClass::class);
        $ruleDefinition->addTag('wordpress.security_rule');
        $container->setDefinition('app.security.test_rule', $ruleDefinition);

        // Should not throw — just silently skip
        $this->pass->process($container);

        $this->assertFalse($container->hasDefinition(SecurityRuleRegistry::class));
    }

    public function testProcessRegistersMultipleTaggedServices(): void
    {
        $container = new ContainerBuilder();

        $registryDefinition = new Definition(SecurityRuleRegistry::class);
        $container->setDefinition(SecurityRuleRegistry::class, $registryDefinition);

        for ($i = 1; $i <= 3; $i++) {
            $def = new Definition(\stdClass::class);
            $def->addTag('wordpress.security_rule');
            $container->setDefinition('app.security.rule_' . $i, $def);
        }

        $this->pass->process($container);

        $calls = $registryDefinition->getMethodCalls();
        $this->assertCount(3, $calls);

        foreach ($calls as $call) {
            $this->assertSame('add', $call[0]);
        }
    }

    public function testProcessIgnoresServicesWithOtherTags(): void
    {
        $container = new ContainerBuilder();

        $registryDefinition = new Definition(SecurityRuleRegistry::class);
        $container->setDefinition(SecurityRuleRegistry::class, $registryDefinition);

        $ruleDefinition = new Definition(\stdClass::class);
        $ruleDefinition->addTag('wordpress.other_tag');
        $container->setDefinition('app.security.other_rule', $ruleDefinition);

        $this->pass->process($container);

        $calls = $registryDefinition->getMethodCalls();
        $this->assertCount(0, $calls);
    }
}
