<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\DisableFileEditor;
use PHPUnit\Framework\TestCase;

class DisableFileEditorTest extends TestCase
{
    public function testImplementsRequiredInterfaces(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisableFileEditor($dispatcher);

        $this->assertInstanceOf(Hooks::class, $rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $rule);
    }

    public function testGetName(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisableFileEditor($dispatcher);

        $this->assertSame('disable_file_editor', $rule->getName());
    }

    public function testHooksRegistersAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);

        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything());

        $rule = new DisableFileEditor($dispatcher);
        $rule->hooks();
    }

    public function testDisableFileEditing(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new DisableFileEditor($dispatcher);

        // Note: define() can only be called once per process.
        // If DISALLOW_FILE_EDIT is already defined, disableFileEditing is a no-op.
        $rule->disableFileEditing();

        $this->assertTrue($rule->isFileEditingDisabled());
    }
}
