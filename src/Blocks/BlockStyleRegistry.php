<?php

namespace Fantassin\Core\WordPress\Blocks;

use Fantassin\Core\WordPress\Contracts\BlockStyleInterface;
use Fantassin\Core\WordPress\Contracts\RegistryInterface;

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
