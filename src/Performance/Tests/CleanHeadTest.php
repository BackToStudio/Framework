<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Hooks\CleanHead;
use PHPUnit\Framework\TestCase;

class CleanHeadTest extends TestCase
{
    public function testHooksAreRegistered(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->atLeast(9))
            ->method('removeAction');
        $dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wp_resource_hints', $this->anything(), 10, 2);

        $hook = new CleanHead($dispatcher);
        $hook->hooks();
    }

    public function testRemoveSWOrgDnsPrefetch(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new CleanHead($dispatcher);

        $hints = ['https://s.w.org', 'https://example.com', '//s.w.org/images'];

        $result = $hook->removeSWOrgDnsPrefetch($hints, 'dns-prefetch');

        $this->assertSame(['https://example.com'], $result);
    }

    public function testKeepsHintsForOtherRelTypes(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new CleanHead($dispatcher);

        $hints = ['https://s.w.org', 'https://example.com'];

        $result = $hook->removeSWOrgDnsPrefetch($hints, 'preconnect');

        $this->assertSame($hints, $result);
    }
}
