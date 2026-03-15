<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

interface DynamicBlock
{

    /**
     * @param array<string, mixed> $attributes
     */
    public function renderBlock(array $attributes, string $content): string;

}
