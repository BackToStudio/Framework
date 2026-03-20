<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Port interface for managing Subresource Integrity hashes.
 */
interface SubresourceIntegrityInterface
{
    /**
     * Register an integrity hash for a script/style handle.
     */
    public function registerHash(string $handle, string $hash): self;

    /**
     * Get the stored hash for a given handle.
     */
    public function getHash(string $handle): ?string;
}
