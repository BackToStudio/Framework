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
}
