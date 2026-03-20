<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Blocks\Contracts;

/**
 * Port interface for block style registration.
 *
 * Abstracts WordPress register_block_style(),
 * allowing the domain layer to remain platform-agnostic.
 */
interface BlockStyleRegistrarInterface
{
    /**
     * @param string $blockName
     * @param array<string, mixed> $styleProperties
     */
    public function register(string $blockName, array $styleProperties): void;
}
