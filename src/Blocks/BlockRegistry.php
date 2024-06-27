<?php

namespace BackTo\Framework\Blocks;

use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\RegistryInterface;

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
