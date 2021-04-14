<?php

namespace Fantassin\Core\WordPress\Blocks;

use Fantassin\Core\WordPress\Contracts\BlockInterface;
use Fantassin\Core\WordPress\Contracts\RegistryInterface;

class BlockRegistry implements RegistryInterface
{

    /**
     * @var BlockInterface[]
     */
    private $blocks = [];

    /**
     * @param BlockInterface $block
     *
     * @return BlockRegistry
     */
    public function add(BlockInterface $block): BlockRegistry
    {
        $this->blocks[] = $block;

        return $this;
    }

    /**
     * @return BlockInterface[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }
}
