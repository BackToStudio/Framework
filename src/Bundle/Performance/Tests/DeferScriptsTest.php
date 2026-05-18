<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Bundle\Performance\Hooks\Assets\DeferScripts;
use PHPUnit\Framework\TestCase;

class DeferScriptsTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private QueryContextInterface $queryContext;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->queryContext = $this->createMock(QueryContextInterface::class);
    }

    public function testHooksAreRegistered(): void
    {
        $this->dispatcher->expects($this->exactly(3))
            ->method('addFilter');

        $hook = new DeferScripts($this->dispatcher, $this->queryContext);
        $hook->hooks();
    }

    public function testHooksRegisteredWithoutQueryStringRemoval(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('script_loader_tag');

        $hook = new DeferScripts($this->dispatcher, $this->queryContext, [], false);
        $hook->hooks();
    }

    public function testAddDeferAttributeToScript(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(false);
        $hook = new DeferScripts($this->dispatcher, $this->queryContext);

        $tag = '<script src="https://example.com/script.js"></script>';
        $result = $hook->addDeferAttribute($tag, 'my-script');

        $this->assertStringContainsString('defer', $result);
    }

    public function testExcludesJqueryFromDefer(): void
    {
        $hook = new DeferScripts($this->dispatcher, $this->queryContext);

        $tag = '<script src="https://example.com/jquery.js"></script>';
        $result = $hook->addDeferAttribute($tag, 'jquery-core');

        $this->assertStringNotContainsString('defer', $result);
    }

    public function testDoesNotDoubleDeferAttribute(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(false);
        $hook = new DeferScripts($this->dispatcher, $this->queryContext);

        $tag = '<script defer src="https://example.com/script.js"></script>';
        $result = $hook->addDeferAttribute($tag, 'my-script');

        $this->assertSame(1, substr_count($result, 'defer'));
    }

    public function testSkipsHookRegistrationOnAdmin(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(true);

        $this->dispatcher->expects($this->never())
            ->method('addFilter');

        $hook = new DeferScripts($this->dispatcher, $this->queryContext);
        $hook->hooks();
    }
}
