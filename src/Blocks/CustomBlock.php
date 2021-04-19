<?php

namespace Fantassin\Core\WordPress\Blocks;

use Fantassin\Core\WordPress\Contracts\BlockInterface;

abstract class CustomBlock implements BlockInterface
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
