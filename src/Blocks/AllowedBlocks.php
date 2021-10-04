<?php

namespace Fantassin\Core\WordPress\Blocks;

use Fantassin\Core\WordPress\Contracts\Hooks;

class AllowedBlocks implements Hooks
{

    /**
     * @var BlockRegistry
     */
    protected $registry;

    public function __construct( BlockRegistry $registry, $allowedBlocks = [] ){
        $this->registry = $registry;
        $this->allowedBlocks = $allowedBlocks;
    }

    public function hooks()
    {
        add_filter('allowed_block_types', [$this, 'allowedBlocks']);
    }

    public function allowedBlocks( $allowedBlocks )
    {
        // if file existe pas {
            // return $allowedBlocks
        // }

        // Get fichier de config
        // $this->allowedBlocks
        $allowedBlocks = [];
        $customBlocks = $this->registry->getBlocks();
        foreach( $customBlocks as $block ){
            if( ! in_array($block->getName(), $allowedBlocks )){
                $allowedBlocks[] = $block->getName();
            }
        }

        return $allowedBlocks;
    }
}
