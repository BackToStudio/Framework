<?php

declare(strict_types=1);

namespace BackTo\Framework\Blocks\Infrastructure;

use BackTo\Framework\Blocks\Contracts\BlockRegistrarInterface;
use WP_Block_Type_Registry;

use function register_block_type;

final class WordPressBlockRegistrar implements BlockRegistrarInterface
{
    /**
     * @param string $blockName
     * @param array<string, mixed> $args
     */
    public function register(string $blockName, array $args = []): void
    {
        register_block_type($blockName, $args);
    }

    public function exists(string $blockName): bool
    {
        return WP_Block_Type_Registry::get_instance()->is_registered($blockName);
    }
}
