<?php

namespace Fantassin\Core\WordPress\Blocks;

use Fantassin\Core\WordPress\Contracts\BlockInterface;

abstract class CustomBlock implements BlockInterface, HasBlockName
{

    protected $name = '';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
