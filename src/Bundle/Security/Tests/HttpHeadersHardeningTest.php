<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\HttpHeadersHardening;
use PHPUnit\Framework\TestCase;

class HttpHeadersHardeningTest extends TestCase
{
    public function testImplementsRequiredInterfaces(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $hardening = new HttpHeadersHardening($dispatcher, $responseEmitter);

        $this->assertInstanceOf(Hooks::class, $hardening);
        $this->assertInstanceOf(SecurityRuleInterface::class, $hardening);
    }

    public function testGetName(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $hardening = new HttpHeadersHardening($dispatcher, $responseEmitter);

        $this->assertSame('http_headers_hardening', $hardening->getName());
    }

    public function testHooksRegistersActions(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $responseEmitter = $this->createMock(ResponseEmitterInterface::class);

        $dispatcher->expects($this->exactly(2))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['send_headers', 'rest_api_init']);
            });

        $hardening = new HttpHeadersHardening($dispatcher, $responseEmitter);
        $hardening->hooks();
    }
}
