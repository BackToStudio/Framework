<?php

namespace Fantassin\Core\WordPress\Blocks;

class CustomBlock implements HasBlockName {

    /**
     * @var string
     */
    protected $name;

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}
