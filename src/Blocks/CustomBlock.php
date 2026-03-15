<?php

declare(strict_types=1);

namespace BackTo\Framework\Blocks;

use BackTo\Framework\Contracts\BlockInterface;

abstract class CustomBlock implements BlockInterface
{

    protected string $name = '';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
