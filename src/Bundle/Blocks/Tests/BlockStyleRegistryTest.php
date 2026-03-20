<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks\Tests;

use BackTo\Framework\Bundle\Blocks\BlockStyleRegistry;
use BackTo\Framework\Contracts\BlockStyleInterface;
use PHPUnit\Framework\TestCase;

class BlockStyleRegistryTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $registry = new BlockStyleRegistry();

        $this->assertSame([], $registry->getBlockStyles());
    }

    public function testAddBlockStyle(): void
    {
        $registry = new BlockStyleRegistry();
        $style = $this->createMock(BlockStyleInterface::class);

        $result = $registry->add($style);

        $this->assertSame($registry, $result);
        $this->assertCount(1, $registry->getBlockStyles());
        $this->assertSame($style, $registry->getBlockStyles()[0]);
    }

    public function testAddMultipleBlockStyles(): void
    {
        $registry = new BlockStyleRegistry();
        $style1 = $this->createMock(BlockStyleInterface::class);
        $style2 = $this->createMock(BlockStyleInterface::class);

        $registry->add($style1)->add($style2);

        $this->assertCount(2, $registry->getBlockStyles());
    }
}
