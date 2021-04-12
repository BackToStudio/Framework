<?php

namespace Fantassin\Core\WordPress\Contracts;

interface DynamicBlock
{

    public function renderBlock(array $attributes, string $content): string;

}
