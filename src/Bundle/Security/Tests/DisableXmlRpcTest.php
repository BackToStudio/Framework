<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Hardening\DisableXmlRpc;
use PHPUnit\Framework\TestCase;

class DisableXmlRpcTest extends TestCase
{
    public function testImplementsRequiredInterfaces(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisableXmlRpc($dispatcher);

        $this->assertInstanceOf(Hooks::class, $rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $rule);
    }

    public function testGetName(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisableXmlRpc($dispatcher);

        $this->assertSame('disable_xmlrpc', $rule->getName());
    }

    public function testHooksRegistersFiltersAndActions(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);

        $dispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['xmlrpc_enabled', 'wp_headers']);
            });

        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('wp', $this->anything());

        $rule = new DisableXmlRpc($dispatcher);
        $rule->hooks();
    }

    public function testRemovePingbackHeader(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisableXmlRpc($dispatcher);

        $headers = [
            'Content-Type' => 'text/html',
            'X-Pingback' => 'https://example.com/xmlrpc.php',
        ];

        $result = $rule->removePingbackHeader($headers);

        $this->assertArrayNotHasKey('X-Pingback', $result);
        $this->assertArrayHasKey('Content-Type', $result);
    }

    public function testRemovePingbackHeaderNoOp(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisableXmlRpc($dispatcher);

        $headers = ['Content-Type' => 'text/html'];
        $result = $rule->removePingbackHeader($headers);

        $this->assertSame($headers, $result);
    }
}
