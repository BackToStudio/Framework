<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface DynamicBlock
{

    public function renderBlock(array $attributes, string $content): string;

}
