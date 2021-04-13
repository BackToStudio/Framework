<?php

namespace Fantassin\Core\WordPress\Blocks;

use Fantassin\Core\WordPress\Contracts\BlockInterface;

abstract class CustomBlock implements BlockInterface {

    /**
     * @return string
     */
    abstract public function getName(): string;
}
