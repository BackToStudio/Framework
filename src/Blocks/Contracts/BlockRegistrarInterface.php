<?php

declare(strict_types=1);

namespace BackTo\Framework\Blocks\Contracts;

/**
 * Port interface for block registration.
 *
 * Abstracts WordPress register_block_type(),
 * allowing the domain layer to remain platform-agnostic.
 */
interface BlockRegistrarInterface
{
    /**
     * @param string $blockName
     * @param array<string, mixed> $args
     */
    public function register(string $blockName, array $args = []): void;

    public function exists(string $blockName): bool;
}
