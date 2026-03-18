<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Hooks\DisableXMLRPC;
use PHPUnit\Framework\TestCase;

class DisableXMLRPCTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    public function testHooksRegistersFiltersAndRemovesAction(): void
    {
        $xmlrpc = new DisableXMLRPC($this->hookDispatcher);

        $this->hookDispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook, $callback) use ($xmlrpc) {
                static $call = 0;
                $call++;

                if ($call === 1) {
                    $this->assertSame('xmlrpc_enabled', $hook);
                    $this->assertSame('__return_false', $callback);
                } else {
                    $this->assertSame('wp_headers', $hook);
                    $this->assertSame([$xmlrpc, 'removePingbackHeader'], $callback);
                }
            });

        $this->hookDispatcher->expects($this->once())
            ->method('removeAction')
            ->with('wp_head', 'rsd_link');

        $xmlrpc->hooks();
    }

    public function testRemovePingbackHeader(): void
    {
        $xmlrpc = new DisableXMLRPC($this->hookDispatcher);

        $headers = [
            'X-Pingback' => 'https://example.com/xmlrpc.php',
            'Content-Type' => 'text/html',
        ];

        $result = $xmlrpc->removePingbackHeader($headers);

        $this->assertArrayNotHasKey('X-Pingback', $result);
        $this->assertSame('text/html', $result['Content-Type']);
    }

    public function testRemovePingbackHeaderHandlesMissingHeader(): void
    {
        $xmlrpc = new DisableXMLRPC($this->hookDispatcher);

        $headers = ['Content-Type' => 'text/html'];

        $result = $xmlrpc->removePingbackHeader($headers);

        $this->assertSame(['Content-Type' => 'text/html'], $result);
    }
}
