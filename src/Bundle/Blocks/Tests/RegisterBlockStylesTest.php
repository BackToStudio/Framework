<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks\Tests;

use BackTo\Framework\Bundle\Blocks\BlockStyleRegistry;
use BackTo\Framework\Bundle\Blocks\Contracts\BlockStyleRegistrarInterface;
use BackTo\Framework\Bundle\Blocks\CustomBlockStyle;
use BackTo\Framework\Bundle\Blocks\RegisterBlockStyles;
use BackTo\Framework\Contracts\BlockStyleInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use PHPUnit\Framework\TestCase;

class TestBlockStyle implements BlockStyleInterface
{
    public function getStyleName(): string
    {
        return 'fancy';
    }

    public function getLabel(): string
    {
        return 'Fancy';
    }

    public function getBlocks(): array
    {
        return ['core/paragraph', 'core/heading'];
    }

    public function getProperties(): array
    {
        return [
            'name' => $this->getStyleName(),
            'label' => $this->getLabel(),
        ];
    }
}

class RegisterBlockStylesTest extends TestCase
{
    public function testHooksRegistersAfterSetupThemeAction(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('after_setup_theme', $this->anything());

        $registrar = $this->createMock(BlockStyleRegistrarInterface::class);
        $registry = new BlockStyleRegistry();

        $registerBlockStyles = new RegisterBlockStyles($registry, $registrar, $hookDispatcher);
        $registerBlockStyles->hooks();
    }

    public function testRegisterCustomBlockStylesCallsRegistrar(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $registrar = $this->createMock(BlockStyleRegistrarInterface::class);
        $registrar->expects($this->exactly(2))
            ->method('register');

        $registry = new BlockStyleRegistry();
        $registry->add(new TestBlockStyle());

        $registerBlockStyles = new RegisterBlockStyles($registry, $registrar, $hookDispatcher);
        $registerBlockStyles->registerCustomBlockStyles();
    }
}
