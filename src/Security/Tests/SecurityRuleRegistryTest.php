<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\SecurityRuleRegistry;
use PHPUnit\Framework\TestCase;

class SecurityRuleRegistryTest extends TestCase
{
    public function testImplementsRegistryInterface(): void
    {
        $registry = new SecurityRuleRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new SecurityRuleRegistry();
        $this->assertCount(0, $registry->getRules());
    }

    public function testAddRule(): void
    {
        $registry = new SecurityRuleRegistry();
        $rule = $this->createMock(SecurityRuleInterface::class);

        $result = $registry->add($rule);

        $this->assertSame($registry, $result);
        $this->assertCount(1, $registry->getRules());
        $this->assertSame($rule, $registry->getRules()[0]);
    }

    public function testGetActiveRuleNames(): void
    {
        $registry = new SecurityRuleRegistry();

        $rule1 = $this->createMock(SecurityRuleInterface::class);
        $rule1->method('getName')->willReturn('http_headers_hardening');

        $rule2 = $this->createMock(SecurityRuleInterface::class);
        $rule2->method('getName')->willReturn('disable_xmlrpc');

        $registry->add($rule1);
        $registry->add($rule2);

        $this->assertSame(['http_headers_hardening', 'disable_xmlrpc'], $registry->getActiveRuleNames());
    }

    public function testDuplicateRulePrevention(): void
    {
        $registry = new SecurityRuleRegistry();

        $rule1 = $this->createMock(SecurityRuleInterface::class);
        $rule1->method('getName')->willReturn('disable_xmlrpc');

        $rule2 = $this->createMock(SecurityRuleInterface::class);
        $rule2->method('getName')->willReturn('disable_xmlrpc');

        $registry->add($rule1);
        $registry->add($rule2);

        $this->assertCount(1, $registry->getRules());
        $this->assertSame($rule1, $registry->getRules()[0]);
    }

    public function testHasRule(): void
    {
        $registry = new SecurityRuleRegistry();

        $rule = $this->createMock(SecurityRuleInterface::class);
        $rule->method('getName')->willReturn('disable_xmlrpc');

        $this->assertFalse($registry->has('disable_xmlrpc'));

        $registry->add($rule);

        $this->assertTrue($registry->has('disable_xmlrpc'));
        $this->assertFalse($registry->has('nonexistent_rule'));
    }

    public function testCount(): void
    {
        $registry = new SecurityRuleRegistry();
        $this->assertSame(0, $registry->count());

        $rule1 = $this->createMock(SecurityRuleInterface::class);
        $rule1->method('getName')->willReturn('rule_a');

        $rule2 = $this->createMock(SecurityRuleInterface::class);
        $rule2->method('getName')->willReturn('rule_b');

        $registry->add($rule1);
        $this->assertSame(1, $registry->count());

        $registry->add($rule2);
        $this->assertSame(2, $registry->count());
    }
}
