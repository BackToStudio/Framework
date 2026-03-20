<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Admin\Tests;

use BackTo\Framework\Bundle\Admin\AddMenuForEditors;
use BackTo\Framework\Bundle\Admin\Contracts\CapabilityManagerInterface;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use PHPUnit\Framework\TestCase;

class AddMenuForEditorsTest extends TestCase
{
    public function testImplementsAdminHooks(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $capabilityManager = $this->createMock(CapabilityManagerInterface::class);
        $menu = new AddMenuForEditors($dispatcher, $capabilityManager);

        $this->assertInstanceOf(AdminHooks::class, $menu);
    }

    public function testHooksRegistersTwoActions(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $capabilityManager = $this->createMock(CapabilityManagerInterface::class);
        $dispatcher->expects($this->exactly(2))
            ->method('addAction')
            ->with(
                $this->logicalOr('admin_head', 'admin_bar_menu'),
                $this->anything(),
            );

        $menu = new AddMenuForEditors($dispatcher, $capabilityManager);
        $menu->hooks();
    }
}
