<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over WordPress nonce operations.
 *
 * Replaces direct calls to wp_create_nonce() and wp_verify_nonce()
 * in domain code, making it testable without WordPress.
 */
interface NonceManagerInterface
{
    /**
     * Create a nonce for the given action.
     */
    public function createNonce(string $action): string;

    /**
     * Verify a nonce for the given action.
     */
    public function verifyNonce(string $nonce, string $action): bool;
}
