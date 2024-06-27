<?php

namespace BackTo\Framework\Blocks;

use BackTo\Framework\Contracts\BlockStyleInterface;
use BackTo\Framework\Contracts\RegistryInterface;

class BlockStyleRegistry implements RegistryInterface
{

    /**
     * @var BlockStyleInterface[]
     */
    private $blockStyles = [];

    /**
     * @param BlockStyleInterface $blockStyle
     *
     * @return BlockStyleRegistry
     */
    public function add(BlockStyleInterface $blockStyle): BlockStyleRegistry
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
