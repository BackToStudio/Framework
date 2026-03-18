<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin\Tests;

use BackTo\Framework\Admin\AddReusableBlockMenu;
use BackTo\Framework\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use PHPUnit\Framework\TestCase;

class AddReusableBlockMenuTest extends TestCase
{
    public function testImplementsAdminHooks(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $registrar = $this->createMock(AdminPageRegistrarInterface::class);
        $menu = new AddReusableBlockMenu($dispatcher, $registrar);

        $this->assertInstanceOf(AdminHooks::class, $menu);
    }

    public function testHooksRegistersAdminMenuAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $registrar = $this->createMock(AdminPageRegistrarInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('admin_menu', $this->anything());

        $menu = new AddReusableBlockMenu($dispatcher, $registrar);
        $menu->hooks();
    }
}
