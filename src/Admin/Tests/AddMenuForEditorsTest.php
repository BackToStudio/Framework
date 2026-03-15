<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin\Tests;

use BackTo\Framework\Admin\AddMenuForEditors;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use PHPUnit\Framework\TestCase;

class AddMenuForEditorsTest extends TestCase
{
    public function testImplementsAdminHooks(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $menu = new AddMenuForEditors($dispatcher);

        $this->assertInstanceOf(AdminHooks::class, $menu);
    }

    public function testHooksRegistersTwoActions(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))
            ->method('addAction')
            ->with(
                $this->logicalOr('admin_head', 'admin_bar_menu'),
                $this->anything(),
            );

        $menu = new AddMenuForEditors($dispatcher);
        $menu->hooks();
    }
}
