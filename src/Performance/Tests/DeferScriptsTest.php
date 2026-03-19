<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Hooks\DeferScripts;
use PHPUnit\Framework\TestCase;

class DeferScriptsTest extends TestCase
{
    public function testHooksAreRegistered(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->exactly(3))
            ->method('addFilter');

        $hook = new DeferScripts($dispatcher);
        $hook->hooks();
    }

    public function testHooksRegisteredWithoutQueryStringRemoval(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('script_loader_tag');

        $hook = new DeferScripts($dispatcher, [], false);
        $hook->hooks();
    }

    public function testAddDeferAttributeToScript(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->method('isAdmin')->willReturn(false);
        $hook = new DeferScripts($dispatcher);

        $tag = '<script src="https://example.com/script.js"></script>';
        $result = $hook->addDeferAttribute($tag, 'my-script');

        $this->assertStringContainsString('defer', $result);
    }

    public function testExcludesJqueryFromDefer(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new DeferScripts($dispatcher);

        $tag = '<script src="https://example.com/jquery.js"></script>';
        $result = $hook->addDeferAttribute($tag, 'jquery-core');

        $this->assertStringNotContainsString('defer', $result);
    }

    public function testDoesNotDoubleDeferAttribute(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->method('isAdmin')->willReturn(false);
        $hook = new DeferScripts($dispatcher);

        $tag = '<script defer src="https://example.com/script.js"></script>';
        $result = $hook->addDeferAttribute($tag, 'my-script');

        $this->assertSame(1, substr_count($result, 'defer'));
    }

    public function testSkipsDeferOnAdmin(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->method('isAdmin')->willReturn(true);
        $hook = new DeferScripts($dispatcher);

        $tag = '<script src="https://example.com/script.js"></script>';
        $result = $hook->addDeferAttribute($tag, 'my-script');

        $this->assertStringNotContainsString('defer', $result);
    }

    public function testSkipsQueryStringRemovalOnAdmin(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->method('isAdmin')->willReturn(true);
        $hook = new DeferScripts($dispatcher);

        $src = 'https://example.com/style.css?ver=1.0';
        $result = $hook->removeVersionQueryString($src);

        $this->assertSame($src, $result);
    }
}
