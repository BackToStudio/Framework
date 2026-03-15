<?php

declare(strict_types=1);

namespace BackTo\Framework\Blocks;

use BackTo\Framework\Contracts\BlockStyleInterface;
use BackTo\Framework\Contracts\RegistryInterface;

class BlockStyleRegistry implements RegistryInterface
{
    /** @var BlockStyleInterface[] */
    private array $blockStyles = [];

    public function add(BlockStyleInterface $blockStyle): self
    {
        $this->blockStyles[] = $blockStyle;

        return $this;
    }

    /**
     * @return BlockStyleInterface[]
     */
    public function getBlockStyles(): array
    {
        return $this->blockStyles;
    }
}
