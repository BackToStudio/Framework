<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks\Tests;

use BackTo\Framework\Bundle\Blocks\BlockRegistry;
use BackTo\Framework\Bundle\Blocks\Contracts\BlockRegistrarInterface;
use BackTo\Framework\Bundle\Blocks\RegisterBlock;
use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\DynamicBlock;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use PHPUnit\Framework\TestCase;

class StaticTestBlock implements BlockInterface
{
    public function getName(): string
    {
        return 'test/static-block';
    }
}

class DynamicTestBlock implements BlockInterface, DynamicBlock
{
    public function getName(): string
    {
        return 'test/dynamic-block';
    }

    public function renderBlock(array $attributes, string $content): string
    {
        return '<div>Dynamic</div>';
    }
}

class RegisterBlockTest extends TestCase
{
    public function testHooksRegistersInitAction(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything());

        $registrar = $this->createMock(BlockRegistrarInterface::class);
        $registry = new BlockRegistry();

        $registerBlock = new RegisterBlock($registry, $registrar, $hookDispatcher);
        $registerBlock->hooks();
    }

    public function testRegisterCustomBlocksCallsRegistrar(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $registrar = $this->createMock(BlockRegistrarInterface::class);
        $registrar->method('exists')->willReturn(false);
        $registrar->expects($this->exactly(2))
            ->method('register');

        $registry = new BlockRegistry();
        $registry->add(new StaticTestBlock());
        $registry->add(new DynamicTestBlock());

        $registerBlock = new RegisterBlock($registry, $registrar, $hookDispatcher);
        $registerBlock->registerCustomBlocks();
    }

    public function testSkipsAlreadyRegisteredBlocks(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $registrar = $this->createMock(BlockRegistrarInterface::class);
        $registrar->method('exists')->willReturn(true);
        $registrar->expects($this->never())
            ->method('register');

        $registry = new BlockRegistry();
        $registry->add(new StaticTestBlock());

        $registerBlock = new RegisterBlock($registry, $registrar, $hookDispatcher);
        $registerBlock->registerCustomBlocks();
    }

    public function testDynamicBlockGetsRenderCallback(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $dynamicBlock = new DynamicTestBlock();

        $registrar = $this->createMock(BlockRegistrarInterface::class);
        $registrar->method('exists')->willReturn(false);
        $registrar->expects($this->once())
            ->method('register')
            ->with(
                'test/dynamic-block',
                $this->callback(function (array $args) use ($dynamicBlock): bool {
                    return isset($args['render_callback'])
                        && $args['render_callback'] === [$dynamicBlock, 'renderBlock'];
                })
            );

        $registry = new BlockRegistry();
        $registry->add($dynamicBlock);

        $registerBlock = new RegisterBlock($registry, $registrar, $hookDispatcher);
        $registerBlock->registerCustomBlocks();
    }

    public function testStaticBlockHasNoRenderCallback(): void
    {
        $hookDispatcher = $this->createMock(HookDispatcherInterface::class);

        $registrar = $this->createMock(BlockRegistrarInterface::class);
        $registrar->method('exists')->willReturn(false);
        $registrar->expects($this->once())
            ->method('register')
            ->with(
                'test/static-block',
                $this->callback(function (array $args): bool {
                    return !isset($args['render_callback']);
                })
            );

        $registry = new BlockRegistry();
        $registry->add(new StaticTestBlock());

        $registerBlock = new RegisterBlock($registry, $registrar, $hookDispatcher);
        $registerBlock->registerCustomBlocks();
    }
}
