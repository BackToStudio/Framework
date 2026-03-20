<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks;

use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\RegistryInterface;

final class BlockRegistry implements RegistryInterface
{
    /** @var BlockInterface[] */
    private array $blocks = [];

    public function add(BlockInterface $block): self
    {
        $this->blocks[] = $block;

        return $this;
    }

    
    public function getBlocks(): array
    {
        return $this->blocks;
    }
}
