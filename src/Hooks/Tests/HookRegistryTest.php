<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Tests;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\AdminHooks;
use BackTo\Framework\Contracts\DeactivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Hooks\HookRegistry;
use PHPUnit\Framework\TestCase;

class FrontHookStub implements HookInterface, Hooks
{
    public bool $called = false;

    public function hooks(): void
    {
        $this->called = true;
    }
}

class AdminHookStub implements HookInterface, AdminHooks
{
    public bool $called = false;

    public function hooks(): void
    {
        $this->called = true;
    }
}

class ActivationHookStub implements HookInterface, ActivationHooks
{
    public function activate()
    {
    }
}

class DeactivationHookStub implements HookInterface, DeactivationHooks
{
    public function deactivate()
    {
    }
}

class HookRegistryTest extends TestCase
{
    private function createMockDispatcher(bool $isAdmin = false): HookDispatcherInterface
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->method('isAdmin')->willReturn($isAdmin);

        return $dispatcher;
    }

    public function testImplementsRegistryInterface(): void
    {
        $registry = new HookRegistry($this->createMockDispatcher());
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new HookRegistry($this->createMockDispatcher());
        $this->assertCount(0, $registry->getHooks());
    }

    public function testAddHook(): void
    {
        $registry = new HookRegistry($this->createMockDispatcher());
        $hook = new FrontHookStub();
        $registry->addHook($hook);
        $this->assertCount(1, $registry->getHooks());
    }

    public function testAddHookReturnsSelf(): void
    {
        $registry = new HookRegistry($this->createMockDispatcher());
        $result = $registry->addHook(new FrontHookStub());
        $this->assertSame($registry, $result);
    }

    public function testRunHooksCallsFrontHooks(): void
    {
        $registry = new HookRegistry($this->createMockDispatcher());
        $hook = new FrontHookStub();
        $registry->addHook($hook);
        $registry->runHooks();
        $this->assertTrue($hook->called);
    }

    public function testRunHooksCallsAdminHooksWhenIsAdmin(): void
    {
        $registry = new HookRegistry($this->createMockDispatcher(true));
        $hook = new AdminHookStub();
        $registry->addHook($hook);
        $registry->runHooks();
        $this->assertTrue($hook->called);
    }

    public function testRunHooksDoesNotCallAdminHooksWhenNotAdmin(): void
    {
        $registry = new HookRegistry($this->createMockDispatcher(false));
        $hook = new AdminHookStub();
        $registry->addHook($hook);
        $registry->runHooks();
        $this->assertFalse($hook->called);
    }

    public function testRunHooksRegistersActivationHookWithPluginFile(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->method('isAdmin')->willReturn(false);
        $dispatcher->expects($this->once())
            ->method('registerActivationHook')
            ->with('plugin.php', $this->anything());

        $registry = new HookRegistry($dispatcher);
        $registry->setPluginFile('plugin.php');
        $registry->addHook(new ActivationHookStub());
        $registry->runHooks();
    }

    public function testRunHooksRegistersDeactivationHookWithPluginFile(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->method('isAdmin')->willReturn(false);
        $dispatcher->expects($this->once())
            ->method('registerDeactivationHook')
            ->with('plugin.php', $this->anything());

        $registry = new HookRegistry($dispatcher);
        $registry->setPluginFile('plugin.php');
        $registry->addHook(new DeactivationHookStub());
        $registry->runHooks();
    }

    public function testRunHooksDoesNotRegisterActivationHookWithoutPluginFile(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->method('isAdmin')->willReturn(false);
        $dispatcher->expects($this->never())
            ->method('registerActivationHook');

        $registry = new HookRegistry($dispatcher);
        $registry->addHook(new ActivationHookStub());
        $registry->runHooks();
    }
}
