<?php

namespace BackTo\Framework\Blocks;

use BackTo\Framework\Contracts\BlockInterface;

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
