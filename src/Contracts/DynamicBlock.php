<?php

namespace BackTo\Framework\Contracts;

interface DynamicBlock
{

    public function renderBlock(array $attributes, string $content): string;

}
