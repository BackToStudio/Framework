<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Theme\Actions\RemoveNavigationFallback;
use BackTo\Framework\Theme\Actions\RemoveWordPressVersion;
use PHPUnit\Framework\TestCase;

class ThemeActionsTest extends TestCase
{
    public function testRemoveNavigationFallbackImplementsHooks(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $action = new RemoveNavigationFallback($dispatcher);

        $this->assertInstanceOf(Hooks::class, $action);
    }

    public function testRemoveNavigationFallbackRegistersFilter(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('block_core_navigation_render_fallback', '__return_false');

        $action = new RemoveNavigationFallback($dispatcher);
        $action->hooks();
    }

    public function testRemoveWordPressVersionImplementsHooks(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $action = new RemoveWordPressVersion($dispatcher);

        $this->assertInstanceOf(Hooks::class, $action);
    }

    public function testRemoveWordPressVersionRegistersHooks(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('removeAction')
            ->with('wp_head', 'wp_generator');
        $dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('the_generator', '__return_false');

        $action = new RemoveWordPressVersion($dispatcher);
        $action->hooks();
    }
}
