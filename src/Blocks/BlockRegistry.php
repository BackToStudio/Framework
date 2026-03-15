<?php

declare(strict_types=1);

namespace BackTo\Framework\Blocks;

use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\RegistryInterface;

class BlockRegistry implements RegistryInterface
{
    /** @var BlockInterface[] */
    private array $blocks = [];

    public function add(BlockInterface $block): self
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
