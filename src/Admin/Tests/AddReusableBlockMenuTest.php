<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin\Tests;

use BackTo\Framework\Admin\AddReusableBlockMenu;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use PHPUnit\Framework\TestCase;

class AddReusableBlockMenuTest extends TestCase
{
    public function testImplementsAdminHooks(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $menu = new AddReusableBlockMenu($dispatcher);

        $this->assertInstanceOf(AdminHooks::class, $menu);
    }

    public function testHooksRegistersAdminMenuAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('admin_menu', $this->anything());

        $menu = new AddReusableBlockMenu($dispatcher);
        $menu->hooks();
    }
}
