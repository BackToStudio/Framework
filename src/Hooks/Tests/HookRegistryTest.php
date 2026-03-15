<?php

namespace BackTo\Framework\Hooks\Tests;

use BackTo\Framework\Contracts\AdminHooks;
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

class HookRegistryTest extends TestCase
{
    public function testImplementsRegistryInterface(): void
    {
        $registry = new HookRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyRegistry(): void
    {
        $registry = new HookRegistry();
        $this->assertCount(0, $registry->getHooks());
    }

    public function testAddHook(): void
    {
        $registry = new HookRegistry();
        $hook = new FrontHookStub();
        $registry->addHook($hook);
        $this->assertCount(1, $registry->getHooks());
    }

    public function testAddHookReturnsSelf(): void
    {
        $registry = new HookRegistry();
        $result = $registry->addHook(new FrontHookStub());
        $this->assertSame($registry, $result);
    }

    public function testRunHooksCallsFrontHooks(): void
    {
        $registry = new HookRegistry();
        $hook = new FrontHookStub();
        $registry->addHook($hook);
        $registry->runHooks();
        $this->assertTrue($hook->called);
    }
}
