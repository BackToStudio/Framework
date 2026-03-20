<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Hardening\DisablePublicCron;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control define() / defined() calls.
 */
class TestableDisablePublicCron extends DisablePublicCron
{
    private bool $cronDisabled = false;
    private bool $defineCalled = false;

    public function setCronDisabled(bool $disabled): void
    {
        $this->cronDisabled = $disabled;
    }

    public function wasDefineCalled(): bool
    {
        return $this->defineCalled;
    }

    protected function isCronDisabled(): bool
    {
        return $this->cronDisabled;
    }

    protected function defineDisableCron(): void
    {
        $this->defineCalled = true;
    }
}

class DisablePublicCronTest extends TestCase
{
    public function testImplementsRequiredInterfaces(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisablePublicCron($dispatcher);

        $this->assertInstanceOf(Hooks::class, $rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $rule);
    }

    public function testGetName(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisablePublicCron($dispatcher);

        $this->assertSame('disable_public_cron', $rule->getName());
    }

    public function testHooksRegistersInitAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything(), 1);

        $rule = new DisablePublicCron($dispatcher);
        $rule->hooks();
    }

    public function testDisableCronDefinesConstantWhenNotAlreadyDefined(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new TestableDisablePublicCron($dispatcher);

        $rule->setCronDisabled(false);
        $rule->disableCron();

        $this->assertTrue($rule->wasDefineCalled());
    }

    public function testDisableCronSkipsWhenAlreadyDisabled(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new TestableDisablePublicCron($dispatcher);

        $rule->setCronDisabled(true);
        $rule->disableCron();

        $this->assertFalse($rule->wasDefineCalled());
    }
}
