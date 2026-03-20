<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Bundle\Performance\Hooks\LimitPostRevisions;
use PHPUnit\Framework\TestCase;

class LimitPostRevisionsTest extends TestCase
{
    public function testHooksAreRegistered(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wp_revisions_to_keep', $this->anything());

        $hook = new LimitPostRevisions($dispatcher, 5);
        $hook->hooks();
    }

    public function testLimitRevisionsReturnsConfiguredValue(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new LimitPostRevisions($dispatcher, 3);

        $this->assertSame(3, $hook->limitRevisions());
    }

    public function testDefaultLimitIsFive(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new LimitPostRevisions($dispatcher);

        $this->assertSame(5, $hook->limitRevisions());
    }
}
