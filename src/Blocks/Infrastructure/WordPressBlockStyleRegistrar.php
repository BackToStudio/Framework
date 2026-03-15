<?php

namespace BackTo\Framework\Blocks\Infrastructure;

use BackTo\Framework\Blocks\Contracts\BlockStyleRegistrarInterface;

use function register_block_style;

/**
 * WordPress adapter for block style registration.
 */
class WordPressBlockStyleRegistrar implements BlockStyleRegistrarInterface
{
    /**
     * @param string $blockName
     * @param array<string, mixed> $styleProperties
     */
    public function register(string $blockName, array $styleProperties): void
    {
        register_block_style($blockName, $styleProperties);
    }
}
