<?php

declare(strict_types=1);

namespace {{namespace}};

use BackTo\Framework\Contracts\BlockInterface;

class {{className}} implements BlockInterface
{
    public function getName(): string
    {
        return '{{name}}';
    }
}
