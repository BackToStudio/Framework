<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin\Tests;

use BackTo\Framework\Admin\AdminPageRegistry;
use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Admin\RegisterAdminPage;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use PHPUnit\Framework\TestCase;

class RegisterAdminPageTest extends TestCase
{
    public function testImplementsAdminHooks(): void
    {
        $register = new RegisterAdminPage(
            new AdminPageRegistry(),
            $this->createMock(AdminPageRegistrarInterface::class),
            $this->createMock(HookDispatcherInterface::class),
        );

        $this->assertInstanceOf(AdminHooks::class, $register);
    }

    public function testHooksRegistersAdminMenuAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('admin_menu', $this->anything());

        $register = new RegisterAdminPage(
            new AdminPageRegistry(),
            $this->createMock(AdminPageRegistrarInterface::class),
            $dispatcher,
        );

        $register->hooks();
    }

    public function testRegisterAdminPagesCallsRegistrar(): void
    {
        $page = $this->createMock(AdminPageInterface::class);
        $page->method('getPageTitle')->willReturn('My Page');
        $page->method('getMenuTitle')->willReturn('My Menu');
        $page->method('getCapability')->willReturn('manage_options');
        $page->method('getMenuSlug')->willReturn('my-page');
        $page->method('getIconUrl')->willReturn('dashicons-admin-generic');
        $page->method('getPosition')->willReturn(30);

        $registry = new AdminPageRegistry();
        $registry->add($page);

        $registrar = $this->createMock(AdminPageRegistrarInterface::class);
        $registrar->expects($this->once())
            ->method('registerMenuPage')
            ->with($this->callback(function (array $args) {
                return $args['page_title'] === 'My Page'
                    && $args['menu_title'] === 'My Menu'
                    && $args['capability'] === 'manage_options'
                    && $args['menu_slug'] === 'my-page'
                    && $args['icon_url'] === 'dashicons-admin-generic'
                    && $args['position'] === 30;
            }));

        $register = new RegisterAdminPage(
            $registry,
            $registrar,
            $this->createMock(HookDispatcherInterface::class),
        );

        $register->registerAdminPages();
    }

    public function testRegisterAdminPagesWithEmptyRegistry(): void
    {
        $registrar = $this->createMock(AdminPageRegistrarInterface::class);
        $registrar->expects($this->never())->method('registerMenuPage');

        $register = new RegisterAdminPage(
            new AdminPageRegistry(),
            $registrar,
            $this->createMock(HookDispatcherInterface::class),
        );

        $register->registerAdminPages();
    }
}
