<?php

namespace Fantassin\Core\WordPress\Blocks;

use Fantassin\Core\WordPress\Contracts\Hooks;

class RegisterBlockStyles implements Hooks
{

    /**
     * @var BlockStyleRegistry
     */
    protected $registry;

    public function __construct(BlockStyleRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function hooks()
    {
        add_action('after_setup_theme', [$this, 'registerCustomBlockStyles']);
    }

    public function registerCustomBlockStyles()
    {
        $blockStyles = $this->registry->getBlockStyles();

        foreach ($blockStyles as $blockStyle) {
            foreach ($blockStyle->getBlocks() as $blockName) {
                register_block_style($blockName, $blockStyle->getProperties());
            }
        }
    }
}
