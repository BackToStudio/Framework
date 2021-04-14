<?php

namespace Fantassin\Core\WordPress\Blocks\Tests;

use Fantassin\Core\WordPress\Blocks\BlockRegistry;
use Fantassin\Core\WordPress\Contracts\BlockInterface;
use Fantassin\Core\WordPress\Contracts\DynamicBlock;
use Fantassin\Core\WordPress\Contracts\RegistryInterface;
use Fantassin\Core\WordPress\PostType\PostTypeRegistry;
use PHPUnit\Framework\TestCase;

class BlockA implements BlockInterface
{

    public function getName(): string
    {
        return 'block-a';
    }
}

class BlockB implements BlockInterface, DynamicBlock
{

    public function getName(): string
    {
        return 'block-b';
    }

    public function renderBlock(array $attributes, string $content): string
    {
        return 'Block B content';
    }
}

class BlockRegistryTest extends TestCase
{
    /**
     * @var BlockRegistry
     */
    private $registry;

    private function givenNewBlockRegistry()
    {
        $this->registry = new BlockRegistry();
        return $this->registry;
    }

    public function thenRegistryShouldHaveNoBlocks(BlockRegistry $registry)
    {
        $this->assertCount(0, $registry->getBlocks());
    }

    private function whenThereAreNoBlocksAdded($registry)
    {
        return $registry;
    }

    private function whenIAddBlock(BlockRegistry $registry, $block)
    {
        $registry->add($block);

        return $registry;
    }


    public function testBlockRegistryShouldImplementsRegistryInterface()
    {
        $registry = $this->givenNewBlockRegistry();
        $this->assertInstanceOf(RegistryInterface::class, $registry);
    }

    public function testEmptyBlocks()
    {
        $registry = $this->givenNewBlockRegistry();
        $registry = $this->whenThereAreNoBlocksAdded($registry);
        $this->thenRegistryShouldHaveNoBlocks($registry);
        $this->isInstanceOf(RegistryInterface::class);
    }

    public function testAddingBlocks()
    {
        $registry = $this->givenNewBlockRegistry();
        $blocksToAdd = [
            new BlockA(),
            new BlockB()
        ];
        foreach ($blocksToAdd as $block) {
            $registry = $this->whenIAddBlock($registry, $block);
        }
        $this->thenRegistryShouldContainsTheseBlocks($registry, $blocksToAdd);
    }

    private function thenRegistryShouldContainsTheseBlocks(BlockRegistry $registry, array $blocks)
    {
        $this->assertCount(count($blocks), $registry->getBlocks());
        foreach ($blocks as $block) {
            $this->assertContains($block, $registry->getBlocks());
        }
    }
}
